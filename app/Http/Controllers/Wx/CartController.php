<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Models\Order\Cart;
use App\Service\Goods\GoodsService;
use App\Service\Order\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends WxController
{
    /**
     * 加入购物车
     * @return JsonResponse
     */
    public function add()
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyInteger('number', 0);
        if ($number <= 0) {
            return $this->badArgument();
        }
        $goods = GoodsService::getInstance()->getGoods($goodsId);
        if (is_null($goods) || !$goods->is_on_sale) {
            return $this->fail(CodeResponse::GOODS_UNSHELVE);
        }

        $product = GoodsService::getInstance()->getGoodsProductById($productId);
        if (is_null($product)) {
            return $this->badArgument();
        }

        $cartProduct = CartService::getInstance()->getCartProduct($this->userId(), $goodsId, $productId);

        if (is_null($cartProduct)) {
            // add new cart product
            CartService::getInstance()->newCart($this->userId(), $goods, $product, $number);

        } else {
            // edit cart product number
            $num = $cartProduct->number + $number;
            if ($num > $product->number ) {
                return $this->fail(CodeResponse::GOODS_NO_STOCK);
            }
            $cartProduct->number = $num;
            $cartProduct->save();
        }
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
}