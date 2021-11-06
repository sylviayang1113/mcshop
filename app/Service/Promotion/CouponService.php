<?php


namespace App\Service\Promotion;


use App\CodeResponse;
use App\Constant;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Service\BaseService;
use Carbon\Carbon;
use PhpParser\Builder;

class CouponService extends BaseService
{
    public function getCoupon($id, $columns = ['*'])
    {
        return Coupon::query()->find($id, $columns);
    }

    public function getCoupons(array $ids, $columns = ['*'])
    {
        return Coupon::query()->whereIn('id', $ids)
            ->get($columns);
    }

    public function list(PageInput $page, $columns = ['*'])
    {
        return Coupon::query()->where('type', Constant::TYPE_COMMON)
            ->where('status', Constant::STATUS_NORMAL)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);

    }

    public function myList($userId, $status, PageInput $page, $columns = ['*'])
    {
        return CouponUser::query()->where('user_id', $userId)
            ->when(!is_null($status), function ( Builder $query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

    public function countCoupon($couponId)
    {
        return CouponUser::query()->where('couponId', $couponId)
            ->count('id');
    }

    public function countCouponByUserId($userId, $couponId)
    {
        return CouponUser::query()->where('couponId', $couponId)
            ->where('user_id', $userId)
            ->count('id');

    }

    /**
     * @param $userId
     * @param $couponId
     * @return bool
     * @throws BusinessException
     */
    public function receive($userId, $couponId)
    {
        $coupon = CouponService::getInstance()->getCoupon($couponId);
        if (is_null($coupon)) {
            $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }

        if ($coupon->total > 0) {
            $fetchedCount = CouponService::getInstance()->countCoupon($couponId);
            if ($fetchedCount >= $coupon->total) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
            }
        }

        if ($coupon->limit > 0) {
            $userFetchedCount = CouponService::getInstance()->countCouponByUserId($userId, $couponId);
            if ($userFetchedCount >= $coupon->limit) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券已经领取过');
            }
        }

        if ($coupon->type != Constant::TYPE_COMMON) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券类型不支持');
        }

        // 优惠券已下架或者过期不能领取
        if ($coupon->status == Constant::STATUS_OUT) {
            $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
        }
        if ($coupon->status == Constant::STATUS_EXPIRED) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券已经过期');
        }

        //用户领券记录
        $couponUser = CouponUser::new();
        if ($coupon->time_type == Constant::TIME_TYPE_TIME) {
            $startTime = $coupon->start_time;
            $endTime = $coupon->end_time;
        } else {
            $startTime = Carbon::now();
            $endTime = $startTime->copy()->addDays($coupon->days);
        }
        $couponUser->fill([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        return $couponId->save();
    }

}