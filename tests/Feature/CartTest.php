<?php


namespace Tests\Feature;


use App\Models\Goods\GoodsProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class CartTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->product = factory(GoodsProduct::class)->create();
    }

    public function testAdd()
    {
        
    }
}