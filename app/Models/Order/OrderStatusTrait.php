<?php


namespace App\Models\Order;


use App\Enums\OrderEnums;

trait OrderStatusTrait
{
    public function canCancelHandle()
    {
        return $this->order_status == OrderEnums::STATUS_CREATE;
    }

    public function canPayHandle()
    {
        return $this->order_status == OrderEnums::STATUS_CREATE;
    }

    public function canShipHandle()
    {
        return $this->order_status == OrderEnums::STATUS_PAY;
    }

    public function canRefundHandle()
    {
        return $this->order_status == OrderEnums::STATUS_PAY;
    }
}