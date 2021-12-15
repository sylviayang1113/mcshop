<?php


namespace Tests\Feature;


use App\Models\Goods\GoodsProduct;
use App\Models\User\User;
use App\Service\Goods\GoodsService;
use App\Service\Order\CartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class CartTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User $user
     */
    private $user;
    /**
     * @var GoodsProduct $product
     */
    private $product;

    private $authHeader;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->product = factory(GoodsProduct::class)->create([
            'number' => 10
        ]);
        $this->authHeader = $this->getAuthHeader($this->user->username, '123456');
    }

    public function testFastAdd()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $resp = $this->post('wx/cart/fastadd', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 5
        ]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertEquals(5, $cart->number);

        $resp->assertJson(["errno" => 0, "errmsg" => "成功", 'data' => $cart->id]);
    }

    public function testAdd()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => 0,
            'product_id' => 0,
            'number' => 1
        ], $this->autherHeader);
        $resp->assertJson(["errno" => 402]);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'product_id' => $this->product->id,
            'number' => 11
        ], $this->autherHeader);
        $resp->assertJson(["errno" => 711, "errmsg" => "库存不足"]);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'product_id' => $this->product->id,
            'number' => 2
        ], $this->autherHeader);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'product_id' => $this->product->id,
            'number' => 3
        ], $this->autherHeader);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "5"]);

        $cart = CartService::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertEquals(5, $cart->number);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'product_id' => $this->product->id,
            'number' => 6
        ], $this->autherHeader);
        $resp->assertJson(["errno" => 711, "errmsg" => "库存不足"]);

    }

    public function testUpdate()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $cart = CartService::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);

        $resp = $this->post('wx/cart/update', [
            'id' =>  $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功"]);
    }

    public function testDelete()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertNotNull($cart);

        $resp = $this->post('ex/cart/delete', [
            'productIds' => [$this->product->id],
            'number' => 2
        ], $this->authHeader);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertNull($cart);

        $resp = $this->post('wx/cart/delete', [
            'productIds' => [],
        ]);
        $resp->assertJson(["errno" => 402]);
    }

    public function testChecked()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);

        $this->assertTrue($cart->checked);

        $resp = $this->post('wx/cart/checked', [
            'productIds' => [$this->product->id],
            'isChecked' => 0
        ], $this->authHeader);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertFalse($cart->checked);

        $resp = $this->post('wx/cart/checked', [
            'productIds' => [$this->product->id],
            'isChecked' => 1
        ], $this->authHeader);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertTrue($cart->checked);
    }

    public function testIndex()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);

        $resp = $this->post('wx/cart/index', [], $this->authHeader);
        $resp->assertJson([
            "errno" => 0, "errmsg" => "成功", "data" => [
            'cartList' => [
                [
                    'goodsId' => $this->product->goods_id,
                    'productId' => $this->product->id,
                ]
            ],
            'cartTotal' => [
                'goodsCount' => 2,
                'goodsAmount' => 1998.00,
                'checkedGoodsCount' => 2,
                'checkedGoodsAmount' => 1998.00
                ]
            ]
        ]);

        $goods = GoodsService::getInstance()->getGoods($this->product->goods_id);
        $goods->is_on_sale = false;
        $goods->save();

        $resp = $this->post('wx/cart/index', [], $this->authHeader);
        $resp->assertJson([
            "errno" => 0, "errmsg" => "成功", "data" => [
                'cartList' => [
                    []
                ],
                'cartTotal' => [
                    'goodsCount' => 0,
                    'goodsAmount' => 0,
                    'checkedGoodsCount' => 0,
                    'checkedGoodsAmount' => 0
                ]
            ]
        ]);
    }
}