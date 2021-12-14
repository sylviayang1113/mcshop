<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Order\Cart;
use App\Service\Goods\GoodsService;
use App\Service\Order\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends WxController
{

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

    public function delete()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        CartService::getInstance()->delete($this->userId(), $productIds);
        $list = CartService::getInstance()->list($this->userId());
        return $this->success($list);

    }

    public function checked()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        $isChecked = $this->verifyBoolean('isChecked');
        CartService::getInstance()->updateChecked(
            $this->userId(),
            $productIds,
            $isChecked == 1);

        $list = CartService::getInstance()->list($this->userId());
        return $this->success($list);
    }
}