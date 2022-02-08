<?php


namespace App\Models\Order;


use App\Enums\OrderEnums;
use function Symfony\Component\Translation\t;

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

    public function canAgreeRefundHandle()
    {
        return $this->order_status == OrderEnums::STATUS_PAY;
    }

    public function canConfirmHandle()
    {
        return $this->order_status == OrderEnums::STATUS_SHIP;
    }

    public function canDeleteHandle()
    {
        return in_array($this->order_status, [
            OrderEnums::STATUS_CANCEL,
            OrderEnums::STATUS_AUTO_CANCEL,
            OrderEnums::STATUS_ADMIN_CANCEL,
            OrderEnums::STATUS_REFUND_CONFIRM,
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ]);
    }

    public function canCommentHandle()
    {
        return in_array($this->order_status, [
           OrderEnums::STATUS_CONFIRM,
           OrderEnums::STATUS_AUTO_CONFIRM
        ]);
    }

    public function canRebuyHandle()
    {
        return in_array($this->order_status, [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ]);
    }

    public function canAfterSaleHandle()
    {
        return in_array($this->order_status, [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ]);
    }

    public function getCanHandleOption()
    {
        return [
            'cancel' => $this->canCancelHandle(),
            'delete' => $this->canDeleteHandle(),
            'pay' => $this->canPayHandle(),
            'comment' => $this->canCommentHandle(),
            'confirm' => $this->canConfirmHandle(),
            'refund' => $this->canRefundHandle(),
            'rebuy' => $this->canRebuyHandle(),
            'aftersale' => $this->canAfterSaleHandle()
        ];
    }

    public function isShipStatus()
    {
        return $this->order_status == OrderEnums::STATUS_SHIP;
    }

}