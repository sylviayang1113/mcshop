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

}