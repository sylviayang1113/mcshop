<?php


namespace Tests\Feature;


use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use DatabaseTransactions;

    public function testList()
    {
        $this->assertLitemallApiGet('wx/coupon/list');
    }

    public function testMyList()
    {
        $this->assertLitemallApiGet('wx/coupon/myList');
        $this->assertLitemallApiGet('wx/coupon/myList?status=0');
        $this->assertLitemallApiGet('wx/coupon/myList?status=1');
        $this->assertLitemallApiGet('wx/coupon/myList?status=2');
    }

}