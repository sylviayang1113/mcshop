<?php


namespace Tests\Feature;


use App\Enums\OrderEnums;
use Tests\TestCase;

class PayTest extends TestCase
{
    public function testWxPay()
    {
        $order1 = $this->getSimpleOrder([[1.01, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order1->id]);

        $order2 = $this->getSimpleOrder([[1.02, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order2->id]);

        $order3 = $this->getSimpleOrder([[1.3, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order3->id]);

        $order4 = $this->getSimpleOrder([[1.31, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order4->id]);

        $order5 = $this->getSimpleOrder([[1.32, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order5->id]);

        $order6 = $this->getSimpleOrder([[1.33, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order6->id]);

        $order7 = $this->getSimpleOrder([[1.34, 1]]);
        $this->post('wx/order/h5pay', ['orderId' => $order7->id]);

        sleep(5);

        $this->assertEquals(OrderEnums::STATUS_PAY, $order1->refresh()->order_status);
        $this->assertEquals(OrderEnums::STATUS_PAY, $order2->refresh()->order_status);
        $this->assertEquals(OrderEnums::STATUS_CREATE, $order3->refresh()->order_status);
        $this->assertEquals(OrderEnums::STATUS_CREATE, $order4->refresh()->order_status);
        $this->assertEquals(OrderEnums::STATUS_PAY, $order5->refresh()->order_status);
        $this->assertEquals(OrderEnums::STATUS_CREATE, $order6->refresh()->order_status);
        $this->assertEquals(OrderEnums::STATUS_CREATE, $order7->refresh()->order_status);
    }
}