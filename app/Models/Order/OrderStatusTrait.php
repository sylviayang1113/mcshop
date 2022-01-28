<?php


namespace App\Models\Order;


use App\Enums\OrderEnums;

trait OrderStatusTrait
{
    public function canCancelHandle()
    {
        return $this->order_status == OrderEnums::STATUS_CREATE;
    }
}