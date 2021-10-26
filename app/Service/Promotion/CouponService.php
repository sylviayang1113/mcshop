<?php


namespace App\Service\Promotion;


use App\Constant;
use App\Inputs\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Service\BaseService;
use phpDocumentor\Reflection\Utils;

class CouponService extends BaseService
{
    public function getCoupons(array $ids, $columns = ['*'])
    {
        return Coupon::query()->whereIn('id', $ids)
            ->where('deleted', 0)->get($columns);
    }

    public function list (PageInput $page, $columns = ['*'])
    {
        return Coupon::query()->where('type', Constant::TYPE_COMMON)
            ->where('status', Constant::STATUS_NORMAL)
            ->Where('deleted', 0)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);

    }

    public function myList($userId, $status, PageInput $page, $columns = ['*'])
    {
        return CouponUser::query()->where('user_id', $userId)
            ->where('status', $status)
            ->where('deleted', 0)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

}