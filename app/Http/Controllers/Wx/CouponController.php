<?php


namespace App\Http\Controllers\Wx;


use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\CouponUser;
use App\Service\Promotion\CouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends WxController
{
    protected $except = ['list'];

    /**
     * 优惠券列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function list ()
    {
        $page = PageInput::new();
        $columns = ['id', 'name', 'desc', 'tag', 'discount', 'min', 'days', 'start_time', 'end_time'];
        $list = CouponService::getInstance()->list($page);
        return $this->successPaginate($list);
    }

    /**
     * @return JsonResponse
     * @throws BusinessException
     */
    public function myList()
    {
        $status = $this->verifyInteger('status', 0);
        $page = PageInput::new();
        $list = CouponService::getInstance()->myList($this->userId(), $status, $page);

        $couponUserList = collect($list->items());
        $couponIds = $couponUserList->pluck('coupon_id')->toArray();
        $coupons = CouponService::getInstance()->getCoupons($couponIds)->keyBy('id');
        $myList = $couponUserList->map(function (CouponUser $item)  use ($coupons) {
            $coupon = $coupons->get($item->coupon_id);
           return [
               'id' => $item->id,
               'cid' => $coupon->id,
               'name' => $coupon->name,
               'desc' => $coupon->desc,
               'tag' => $coupon->desc,
               'min' => $coupon->tag,
               'discount' => $coupon->min,
               'startTime' => $item->start_time,
               'endTime' => $item->end_time,
               'available' => false
           ];
        });
        $list = $this->paginate($list, $myList);
        return $this->success($list);
    }

    public function selectList() {}

    public function receive() {}

}