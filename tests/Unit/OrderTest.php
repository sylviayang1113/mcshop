<?php


namespace Tests\Unit;


use App\Inputs\OrderSubmitInput;
use App\Jobs\OrderUnpaidTimeEndJob;
use App\Models\Order\OrderGoods;
use App\Service\Goods\GoodsService;
use App\Service\Order\OrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testReduceStock()
    {
        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->groupon()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product1->id], false);
        // 19.56*5+10.6*3=129.6
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->user->id);
        OrderService::getInstance()->reduceProductsStock($checkedGoodsList);

        $this->assertEquals($product2->number - 5, $product2->refresh()->number);
        $this->assertEquals($product3->number - 3, $product3->refresh()->number);
    }

    public function testSubmit()
    {
        $this->user = User::factory()->AddressDefault()->create();
        $address = AddressServices::getInstance()->getDefaultAddress($this->user->id);

        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->groupon()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product1->id], false);
        // 19.56*5+10.6*3=129.6
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->user->id);
        $grouponPrice = 0;
        $rulesId = GrouponRules::whereGoodsId($product2->goods_id)->first()->id ?? null;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList, $rulesId,
            $grouponPrice);
        $this->assertEquals(129.6, $checkedGoodsPrice);

        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'grouponRulesId' => $rulesId,
            'message' => '备注'
        ]);


        $order = OrderService::getInstance()->submit($this->user->id, $input);
        $this->assertNotEmpty($order->id);
        $this->assertEquals($checkedGoodsPrice, $order->goods_price);
        $this->assertEquals($checkedGoodsPrice, $order->actual_price);
        $this->assertEquals($checkedGoodsPrice, $order->order_price);
        $this->assertEquals($grouponPrice, $order->groupon_price);
        $this->assertEquals('备注', $order->message);

        $list = OrderGoods::whereOrderId($order->id)->get()->toArray();
        $this->assertEquals(2, count($list));

        $productIds = CartServices::getInstance()->getCartList($this->user->id)->pluck('product_id')->toArray();
        $this->assertEquals([$product1->id], $productIds);
    }

    public function testJob()
    {
        dispatch(new OrderUnpaidTimeEndJob(1, 2));
    }

    private function getOrder()
    {
        $this->user = User::factory()->AddressDefault()->create();
        $address = AddressServices::getInstance()->getDefaultAddress($this->user->id);

        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->groupon()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product1->id], false);
        // 19.56*5+10.6*3=129.6
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->user->id);
        $grouponPrice = 0;
        $rulesId = GrouponRules::whereGoodsId($product2->goods_id)->first()->id ?? null;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList, $rulesId,
            $grouponPrice);
        $this->assertEquals(129.6, $checkedGoodsPrice);

        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'grouponRulesId' => $rulesId,
            'message' => '备注'
        ]);
        $order = OrderService::getInstance()->submit($this->user->id, $input);
        return $order;
    }

    public function testCancel()
    {
        $order = $this->getOrder();
        OrderService::getInstance()->userCancel($this->user->id, $order->id);
        $this->assertEquals(OrderEnums::STATUS_CANCEL, $order->refresh()->order_status);
        $goodsList = OrderService::getInstance()->getOrderGoodsList($order->id);
        $productIds = $goodsList->pluck('product_id')->toArray();
        $products = GoodsService::getInstance()->getGoodsProductsByIds($productIds);
        $this->assertEquals([100, 100], $products)->pluck('number')->toArray();
    }
}