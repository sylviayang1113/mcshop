<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Inputs\OrderSubmitInput;
use App\Models\Order\Order;
use App\Service\Order\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yansongda\Pay\Pay;

class OrderController extends WxController
{

    protected $except = ['wxNotify'];
    /**
     * 提交订单
     * @return JsonResponse
     * @throws BusinessException
     * @throws Throwable
     */
    public function submit()
    {
        $input = OrderSubmitInput::new();

        $lockKey = sprintf('order_submit_%s_%s', $this->userId(),  md5(serialize($input)));
        $lock = Cache::lock($lockKey, 5);
        if (!$lock->get()) {
            return $this->fail(CodeResponse::FAIL, '请勿重复请求');
        }
        
        $order = DB::transaction(function () use ($input) {
            return OrderService::getInstance()->submit($this->userId(), $input);
        });
        return $this->success([
            'orderId' => $order->id,
            'groupLikeId' => $input->grouponLinkId ?? 0
        ]);
    }

    /**
     * 用户主动取消订单
     * @return JsonResponse
     * @throws BusinessException
     */
    public function cancel()
    {
        $orderId = $this->verifyId('orderId');
        OrderService::getInstance()->userCancel($this->userId(), $orderId);
        return $this->success();
    }

    /**
     * 申请退款
     * @return JsonResponse
     * @throws BusinessException
     */
    public function refund()
    {
        $orderId = $this->verifyId('orderId');
        OrderService::getInstance()->refund($this->userId(), $orderId);
        return $this->success();
    }

    /**
     * 确认收货
     * @return JsonResponse
     * @throws BusinessException
     */
    public function confirm()
    {
        $orderId = $this->verifyId('orderId');
        OrderService::getInstance()->confirm($this->userId(), $orderId);
        return $this->success();
    }

    public function delete()
    {
        $orderId = $this->verifyId('orderId');
        OrderService::getInstance()->delete($this->userId(), $orderId);
        return $this->success();
    }

    public function detail()
    {
        $orderId = $this->verifyId('orderId');
        $detail = OrderService::getInstance()->detail($this->userId, $orderId);
        return $this->success($detail);
    }

    public function h5pay()
    {
        $orderId = $this->verifyId('oderId');
        $order = OrderService::getInstance()->getWxPayOrder($this->userId(), $orderId);
        return Pay::wechat()->wap($order);
    }

    public function wxNotify()
    {
        $data = Pay::wechat()->verify();
        $data = $data->toArray();

        Log::info('wxNotify', $data);
        DB::transaction(function () use ($data) {
            OrderService::getInstance()->WxNotify();
        });
        return Pay::wechat()->success();

    }
}