<?php


namespace App\Service\Order;


use App\CodeResponse;
use App\Enums\OrderEnums;
use App\Exceptions\BusinessException;
use App\Inputs\OrderSubmitInput;
use App\Jobs\OrderUnpaidTimeEndJob;
use App\Models\Goods\GoodsProduct;
use App\Models\Order\Order;
use App\Models\Order\OrderGoods;
use App\Notifications\NewPaidOrderEmailNotify;
use App\Notifications\NewPaidOrderSMSNotify;
use App\Service\BaseService;
use App\Service\Goods\GoodsService;
use App\Service\Promotion\CouponService;
use App\Service\Promotion\GrouponService;
use App\Service\SystemService;
use App\Service\User\UserService;
use App\Services\User\AddressService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class OrderService extends BaseService
{
    /**
     * 提交订单
     * @param $userId
     * @param OrderSubmitInput $input
     * @return Order|void
     * @throws BusinessException
     */
    public function submit($userId, OrderSubmitInput $input)
    {
        // 验证团购规则的有效性
        if (!empty($input->grouponRulesId)) {
            GrouponService::getInstance()->checkGrouponValid($userId, $input->grouponRulesId);
        }
        $address = AddressService::getInstance()->getAddress($userId, $input->addressId);
        if (empty($address)) {
            return $this->throwBadArgumentValue();
        }

        // 获取购物车的商品列表
        $checkedGoodsList = CartService::getInstance()->getCheckedCartList($this->userId(), $input->cartId);

        // 计算订单总金额
        $grouponPrice = 0;
        $checkedGoodsPrice = CartService::getInstance()->getCartPriceCutGroupon($checkedGoodsList,
            $input->grouponRulesId,
            $grouponPrice);
        $couponPrice = 0;
        if ($input->couponId > 0) {
            $coupon = CouponService::getInstance()->getCoupon($input->couponId);
            $couponUser = CouponService::getCouponUser();
            $is = CouponService::getInstance()->checkCouponAndPrice($coupon, $couponUser, $checkedGoodsPrice);
           if ($is) {
               $couponPrice = $coupon->discount;
           }
        }

        // 运费
        $freightPrice = $this->getFreight($checkedGoodsPrice);

        // 计算订单金额
        $orderTotalPrice = bcadd($checkedGoodsPrice, $freightPrice);
        $orderTotalPrice = bcsub($orderTotalPrice, $couponPrice);
        $orderTotalPrice = max(0, $orderTotalPrice);

        $order = Order::new();
        $order->user_id = $userId;
        $order->order_sn = $this->generateOrderSn();
        $order->order_status = OrderEnums::STATUS_CREATE;
        $order->consignee = $address->name;
        $order->mobile = $address->tel;
        $order->address = $address->province.$address->city.$address->county." ".$address->address_detail;
        $order->message = $input->message;
        $order->goods_price = $checkedGoodsPrice;
        $order->freight_price = $freightPrice;
        $order->integral_price = 0;
        $order->coupon_price = $couponPrice;
        $order->order_price = $orderTotalPrice;
        $order->actual_price = $orderTotalPrice;
        $order->groupon_price = $grouponPrice;
        $order->save();

        // 写入订单商品记录
        $this->saveOrderGoods($checkedGoodsPrice, $order->id);

        // 清理购物车
        CartService::getInstance()->clearCartGoods();

        // 减库存
        $this->reduceProductStock($checkedGoodsList);

        // 添加团购记录
        GrouponService::getInstance()->openOrJoinGroupon($userId, $order->id, $input->grouponRulesId,
            $input->grouponLinkId);

        // 设置超时任务
        dispatch(new OrderUnpaidTimeEndJob($userId, $order->id));

        return $order;
    }

    /**
     * 减库存
     * @param  Cart[]|Collection  $goodsList
     * @throws BusinessException
     */
    public function reduceProductsStock($goodsList)
    {
        $productIds = $goodsList->pluck('produc_id')->toArray();
        $products = GoodsService::getInstance()->getGoodsProductsByIds($productIds)->keyBy('id');
        foreach ($goodsList as $cart) {
            /** @var GoodsProduct $product */
            $product = $products->get($cart->product_id);
            if (empty($product)) {
                $this->throwBadArgumentValue();
            }
            if ($product->number < $cart->number) {
                $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
            }
            $row = GoodsService::getInstance()->reduceStock($product->id, $cart->number);
            if ($row == 0) {
                $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
            }
        }
    }

    /**
     * @param  Cart[] $checkGoodsList
     * @param $orderId
     */
    private function saveOrderGoods($checkGoodsList, $orderId)
    {
        foreach ($checkGoodsList as $cart) {
            $orderGoods = OrderGoods::new();
            $orderGoods->order_id = $orderId;
            $orderGoods->goods_id = $cart->goods_id;
            $orderGoods->goods_sn = $cart->goods_sn;
            $orderGoods->product_id = $cart->product_id;
            $orderGoods->goods_name = $cart->goods_name;
            $orderGoods->pic_url = $cart->pic_url;
            $orderGoods->price = $cart->price;
            $orderGoods->number = $cart->number;
            $orderGoods->specifications = $cart->specifications;
            $orderGoods->save();
        }
    }

    /**
     * 生成订单编号
     * @return mixed
     * @throws BusinessException
     */
    public function generateOrderSn()
    {
        return retry(5, function () {
            $orderSn = date('YmdHis').Str::random(6);
            if (!$this->isOrderSnUsed($orderSn)) {
                return $orderSn;
            }
            \Log::warning('订单号获取失败');
            $this->throwBusinessException(CodeResponse::FAIL, '订单号获取失败');
        });
    }

    public function isOrderSnUsed($orderSn)
    {
        return Order::query()->where('order_sn');
    }

    /**
     * 获取运费
     * @param $price
     * @return float|int
     */
    public function getFreight($price)
    {
        // 运费
        $freightPrice = 0;
        $minFreightMin = SystemService::getInstance()->getFreightMin();
        if (bccomp($minFreightMin, $price) == 1) {
            $freightPrice = SystemService::getInstance()->getFreightValue();
        }
        return $freightPrice;
    }

    public function getOrderByUserIdAndId($userId, $orderId)
    {
        return Order::query()->where('user_id', $userId)->find($orderId);
    }

    /**
     * @param $userId
     * @param $orderId
     */
    public function userCancel($userId, $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            $this->cancel($userId, $orderId, 'user');
        });
    }

    /**
     * @param $userId
     * @param $orderId
     */
    public function systemCancel($userId, $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            $this->cancel($userId, $orderId, 'system');
        });
    }

    /**
     * @param $userId
     * @param $orderId
     */
    public function adminCancel($userId, $orderId)
    {
        $this->cancel($userId, $orderId, 'admin');
    }

    public function getOrderGoodsList($orderId)
    {
        return OrderGoods::query()->where('order_id', $orderId)->get();
    }

    /**
     * 订单取消
     * @param $userId
     * @param $orderId
     * @param string $role 支持： user/admin/system
     * @return bool
     * @throws BusinessException
     */
    private function cancel($userId, $orderId, $role = 'user')
    {
        $order = $this->getOrderByUserIdAndId($orderId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }

        if (!$order->canCancelHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '订单不能取消');
        }

        switch ($role) {
            case 'system':
                $order->order_status = OrderEnums::STATUS_AUTO_CANCEL;
                break;
            case 'admin':
                $order->order_status = OrderEnums::STATUS_ADMIN_CANCEL;
        }

        Order::query()->where('update_time', $order->update_time)->where('id', $order->id)
            ->where('order_status', OrderEnums::STATUS_CREATE)
            ->update(['order_status' =>OrderEnums::STATUS_CANCEL]);

        if (!$order->cas() == 0) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }

        $orderGoods = $this->getOrderGoodsList($orderId);
        foreach ($orderGoods as $goods) {
            $row = GoodsService::getInstance()->addStock($goods->produc_id, $goods->number);
            if ($row) {

            }
        }

        return true;
    }

    public function payOrder(Order $order, $payId)
    {
        if (!$order->canPayHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_PAY_FAIL, '订单不能支付');
        }
        $order->pay_id = $payId;
        $order->pay_time = now()->toDateTimeString();
        $order->order_status = OrderEnums::STATUS_PAY;
        if ($order->cas()) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }
        GrouponService::getInstance()->payGrouponOrder($order->id);
        Notification::route('mail', '')->notify(new NewPaidOrderEmailNotify($order->id));
        $user = UserService::getInstance()->getUserById($order->user_id);
        $user->notify(new NewPaidOrderSMSNotify());
    }

    public function ship($userId, $orderId, $shipSn, $shipChannel)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }

        if (!$order->canShipHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能发货');
        }
        $order->order_status = OrderEnums::STATUS_SHIP;
        $order->ship_sn = $shipSn;
        $order->ship_channel = $shipChannel;
        $order->ship_time = now()->toDateTimeString();
        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }
        
        return $order;
    }
}