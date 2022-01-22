<?php


namespace App\Http\Controllers\Wx;


use App\Exceptions\BusinessException;
use App\Inputs\OrderSubmitInput;
use App\Service\Order\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends WxController
{
    /**
     * 提交订单
     * @return JsonResponse
     * @throws BusinessException
     * @throws Throwable
     */
    public function submit()
    {
        $input = OrderSubmitInput::new();
        $order = DB::transaction(function () use ($input) {
            return OrderService::getInstance()->submit($this->userId(), $input);
        });
        return $this->success([
            'orderId' => $order->id,
            'groupLikeId' => $input->grouponLinkId ?? 0
        ]);
    }
}