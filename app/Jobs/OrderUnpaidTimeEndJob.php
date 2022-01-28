<?php

namespace App\Jobs;

use App\Exceptions\BusinessException;
use App\Service\Order\OrderService;
use App\Service\SystemService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderUnpaidTimeEndJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;
    private $orderId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $orderId)
    {
        $this->userId = $userId;
        $this->orderId = $orderId;
        $delayTime = SystemService::getInstance()->getOrderUnpaidDelayMinutes();
        $this->delay(now()->addMinutes(30));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            OrderService::getInstance()->systemCancel($this->userId, $this->orderId);
        } catch (BusinessException $exception) {
        }
    }
}
