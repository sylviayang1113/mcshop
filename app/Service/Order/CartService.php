<?php


namespace App\Service\Order;


use App\CodeResponse;
use App\Models\Order\Cart;
use App\Service\BaseService;
use Exception;

class CartService extends BaseService
{
    public function getCartById($userId, $id)
    {
        return Cart::query()->where('user_id', $userId)->where('id', $id)->first();
    }
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
        $cart->goods_id = $goods->id;
        $cart->product_id = $product->id;
        $cart->save();
        return $cart;
    }

    /**
     * @param $userId
     * @param $productIds
     * @return bool|int|mixed|null
     * @throws Exception
     */
    public function delete($userId, $productIds)
    {
        return Cart::query()->where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->delete();
    }

    public function updateChecked($userId, $productIds, $isChecked)
    {
        return Cart::query()->where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->update(['checked' => $isChecked]);
    }

    public function list($userId)
    {
        return [];
    }
}