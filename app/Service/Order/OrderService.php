<?php


namespace App\Service\Order;


use App\CodeResponse;
use App\Enums\OrderEnums;
use App\Exceptions\BusinessException;
use App\Inputs\OrderSubmitInput;
use App\Models\Order\Order;
use App\Service\BaseService;
use App\Service\Promotion\CouponService;
use App\Service\Promotion\GrouponService;
use App\Service\SystemService;
use App\Services\User\AddressService;
use Illuminate\Support\Str;

class OrderService extends BaseService
{
    /**
     * 提交订单
     * @param $userId
     * @param OrderSubmitInput $input
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

        // TODO 设置超时任务
    }

    public function reduceProductStock($goodsList)
    {
        // TODO
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
}