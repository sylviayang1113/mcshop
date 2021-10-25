<?php


namespace App\Service\Promotion;


use App\Constant;
use App\Inputs\PageInput;
use App\Models\Promotion\Coupon;
use App\Service\BaseService;

class CouponService extends BaseService
{
    public function list (PageInput $page, $columns = ['*'])
    {
        return Coupon::query()->where('type', Constant::TYPE_COMMON)
            ->where('status', Constant::STATUS_NORMAL)
            ->Where('deleted', 0)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);

    }
}