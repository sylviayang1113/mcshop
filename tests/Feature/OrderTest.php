<?php


namespace Tests\Feature;


use App\Models\Order\ExpressService;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testDetail()
    {
        $this->assertLitemallApiGet('wx/order/detail?orderId=1', ['data.orderInfo.expName', 'data.orderInfo.expCode', 'data.orderInfo.expNo']);
    }

    public function testExpress()
    {
        $ret = ExpressService::getInstance()->getOrderTraces('YTO', '12345678');
    }
}