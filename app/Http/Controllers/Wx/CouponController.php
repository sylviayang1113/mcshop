<?php


namespace App\Http\Controllers\Wx;


use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
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

    public function myList() {}

    public function selectList() {}

    public function receive() {}

}