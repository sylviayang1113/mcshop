<?php


namespace App\Service\Order;


use App\CodeResponse;
use App\Models\Order\Cart;
use App\Service\BaseService;

class CartService extends BaseService
{
    public function getCartProduct($userId, $goodsId, $productId)
    {
        return Cart::query()->where('user_id', $userId)->where('goods_id', $goodsId)
            ->where('product_id', $productId)->first();
    }

    public function countCartProduct($userId) {
        return Cart::query()->where('user_id', $userId)->sum('number');
    }

    public function newCart($userId, Goods $goods, GoodsProduct $product, $number)
    {
        if ($number > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }
        $cart = Cart::new();
        $cart->goods_sn = $goods->goods_sn;
        $cart->goods_name = $goods->name;
        $cart->pic_url = $product->url ?: $product->pic_url;
        $cart->price = $product->price;
        $cart->specifications = $product->specifications;
        $cart->user_id = $userId;
        $cart->checked = true;
        $cart->number = $number;
        $cart->save();
        return $cart;
    }
}