<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Order\Cart;
use App\Service\Goods\GoodsService;
use App\Service\Order\CartService;
use App\Service\User\AddressService;
use Exception;
use Illuminate\Http\JsonResponse;

class CartController extends WxController
{

    /**
     * 购物车列表
     * @return JsonResponse
     * @throws Exception
     */
    public function index ()
    {
        $list = CartService::getInstance()->getValidCartList($this->userId());
        $goodsCount = 0;
        $goodsAmount = 0;
        $checkedGoodsCount = 0;
        $checkedGoodsAmount = 0;
        foreach ($list as $item) {
            $goodsCount += $item->number;
            $amount = bcmul($item->price, $item->number, 2);
            $goodsAmount = bcadd($goodsAmount, $amount, 2);
            if ($item->checked) {
                $checkedGoodsCount+= $item->number;
                $checkedGoodsAmount = bcadd($checkedGoodsAmount, $amount, 2);
            }
        }
        return $this->success(
            [
                'cartList' => $list->toArray(),
                'cartTotal' => [
                    'goodsCount' => $goodsCount,
                    'goodsAmount' => (double) $goodsAmount,
                    'checkedGoodsCount' => $checkedGoodsCount,
                    'checkedGoodsAmount' => (double) $checkedGoodsAmount
                ]
            ]
        );
    }


    /**
     * 立即购买
     * @return JsonResponse
     * @throws BusinessException
     */
    public function fastadd()
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);
        $cart = CartService::getInstance()->fastadd($this->userId(), $goodsId, $productId, $number);
        return $this->success($cart->id);
    }

    /**
     * 加入购物车
     * @return JsonResponse
     */
    public function add()
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);
        CartService::getInstance()->add($this->userId(), $goodsId, $productId, $number);
        $count = CartService::getInstance()->countCartProduct($this->userId());
        return $this->success($count);
    }

    /**
     * 获取购物车商品的件数
     * @return JsonResponse
     */
    public function goodsCount() {
        $count = CartService::getInstance()->countCartProduct($this->userId());
        return $this->success($count);
    }

    /**
     * @return JsonResponse
     * @throws BusinessException
     */
    public function update()
    {
        $id = $this->verifyId('id', 0);
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number');
        $cart = CartService::getInstance()->getCartById($this->userId(), $id);
        if (is_null($cart)) {
            return $this->badArgumentValue();
        }

        if ($cart->goods_id != $goodsId || $cart->product_id != $productId) {
            return $this->badArgumentValue();
        }

        $goods = GoodsService::getInstance()->getGoods($goodsId);
        if (is_null($goods) || $goods->is_on_sale) {
            return $this->fail(CodeResponse::GOODS_UNSHELVE);
        }

        $product = GoodsService::getInstance()->getGoodsProductById($productId);
        if (is_null($product) || $product->number < $number) {
            return $this->fail(CodeResponse::GOODS_NO_STOCK);
        }

        $cart->number = $number;
        $ret = $cart->save();
        return $this->failOrSuccess($ret);
    }

    /**
     * @return JsonResponse
     * @throws BusinessException
     */
    public function delete()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        CartService::getInstance()->delete($this->userId(), $productIds);
        return $this->index();

    }

    /**
     * @return JsonResponse
     * @throws BusinessException
     */
    public function checked()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        $isChecked = $this->verifyBoolean('isChecked');
        CartService::getInstance()->updateChecked(
            $this->userId(),
            $productIds,
            $isChecked == 1);

        return $this->index();
    }

    public function checkout()
    {
        $cartId = $this->verifyInteger('cartId');
        $addressId = $this->verifyInteger('addressId');
        $couponId = $this->verifyInteger('couponId');
        $userCouponId = $this->verifyInteger('userCouponId');
        $grouponRulesId = $this->verifyInteger('grouponRulesId');

        // 获取地址
        if (empty($addressId)) {
            AddressService::getInstance()->getDefaultAddress($this->userId());
        } else {
            $address = AddressService::getInstance()->getAddress($this->userId(), $addressId);
            if (empty($address)) {
                return $this->badArgumentValue();
            }
        }

        // 获取购物车的商品列表
        if (empty($cartId)) {
            $checkedGoodsList = CartService::getInstance()->getCheckedCartList($this->userId());
        } else {
            $cart = CartService::getInstance()->getCartById($this->userId(), $cartId);
            if (empty($cart)) {
                return $this->badArgumentValue();
            }
            $checkedGoodsList = collect([$cart]);
        }

        
    }
}