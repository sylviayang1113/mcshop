<?php


namespace App\Service\Order;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Goods\GoodsProduct;
use App\Models\Order\Cart;
use App\Service\BaseService;
use App\Service\Goods\GoodsService;
use App\Service\Promotion\GrouponService;
use Exception;
use Illuminate\Support\Collection;

class CartService extends BaseService
{

    public function getCartList($userId)
    {
        return Cart::query()->where('user_id', $userId)->get();
    }

    public function getCheckedCart($userId)
    {
        return Cart::query()->where('user_id', $userId)
            ->where('checked', 1)->get();
    }

    /**
     * 获取一选择的购物车商品列表
     * @param $userId
     * @param $cartId
     * @return Collection|mixed
     */
    public function getCheckedCartList($userId, $cartId = null)
    {
        if (empty($cartId)) {
            $checkedGoodsList = $this->getCheckedCartList($userId);
        } else {
            $cart = $this->getCartById($userId, $cartId);
            if (empty($cart)) {
                $this->throwBadArgumentValue();
            }
            $checkedGoodsList = collect([$cart]);
        }
        return $checkedGoodsList;
    }

    public function getCartPriceCutGroupon($checkedGoodsList, $grouponRulesId, &$grouponPrice = 0)
    {
        $grouponRules = GrouponService::getInstance()->getGrouponRulesById($grouponRulesId);
        $checkedGoodsPrice = 0;
        foreach ($checkedGoodsList as $cart) {
            if ($grouponRules && $grouponRules->goods_id == $cart->goods_id) {
                $grouponPrice  = bcmul($grouponRules->discount, $cart->number, 2);
                $price = bcsub($cart->price, $grouponRules->discount, 2);
            } else {
                $price = $cart->price;
            }
            $price = bcmul($price, $cart->number, 2);
            $checkedGoodsPrice = bcadd($checkedGoodsPrice, $price, 2);
        }
        return $checkedGoodsPrice;
    }


    public function getValidCartList($userId)
    {
        $list = $this->getCartList($userId);
        $goodsIds = $list->pluck('goods_id')->toArray();
        $goodsList = GoodsService::getInstance()->getGoodsListByIds($goodsIds)->keyBy('id');
        $invalidCartIds = [];
        $list->filter(function (Cart $cart) use ($goodsList, &$invalidCartIds) {
            /**
             * @var $goods
             */
            $goods = $goodsList->get($cart->goods_id);
            $isValid = !empty($goods) && $goods->is_on_sale;
            if (!$isValid) {
                $invalidCartIds[] = $cart->id;
            }
            return $isValid;
        });
        $this->deleteCartList($invalidCartIds);
        return $list;
    }

    /**
     * @param $ids
     * @return bool|int|mixed|null
     * @throws Exception
     */
    public function deleteCartList($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        return Cart::query()->whereIn('id', $ids)->delete();
    }

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

    /**
     * @param $goodsId
     * @param $productId
     * @return array
     * @throws BusinessException
     */
    public function getGoodsInfo($goodsId, $productId)
    {
        $goods = GoodsService::getInstance()->getGoods($goodsId);
        if (is_null($goods) || !$goods->is_on_sale) {
            $this->throwBusinessException(CodeResponse::GOODS_UNSHELVE);
        }

        $product = GoodsService::getInstance()->getGoodsProductById($productId);
        if (is_null($product)) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }
        return [$goods, $product];
    }

    /**
     * 添加购物车
     * @param $userId
     * @param $goodsId
     * @param $productId
     * @param $number
     * @return Cart
     * @throws BusinessException
     */
    public function add($userId, $goodsId, $productId, $number)
    {
        list($goods, $product) = $this->getGoodsInfo($goodsId, $productId);
        $cartProduct = $this->getCartProduct($userId, $goodsId, $productId);
        if (is_null($cartProduct)) {
            $this->newCart($userId, $goods, $product, $number);
        } else {
            $number = $cartProduct->number + $number;
            return $this->editCart($cartProduct, $product, $number);
        }
    }

    /**
     * @param $userId
     * @param $goodsId
     * @param $productId
     * @param $number
     * @return Cart
     * @throws BusinessException
     */
    public function fastadd($userId, $goodsId, $productId, $number)
    {
        list($goods, $product) = $this->getGoodsInfo($goodsId, $productId);
        $cartProduct = $this->getCartProduct($userId, $goodsId, $productId);
        if (is_null($cartProduct)) {
            $this->newCart($userId, $goods, $product, $number);
        } else {
            return $this->editCart($cartProduct, $product, $number);
        }

    }

    /**
     * @param Cart $existCart
     * @param GoodsProduct $product
     * @param int $number
     * @return Cart
     */
    public function editCart($existCart, $product, $num)
    {
        if ($num > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }
        $existCart->nubmer = $num;
        $existCart->save();
        return $existCart;
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

}