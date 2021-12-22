<?php


namespace App\Service\Promotion;


use App\CodeResponse;
use App\Constant;
use App\Enums\CouponEnums;
use App\Enums\CouponUserEnums;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Service\BaseService;
use Carbon\Carbon;
use PhpParser\Builder;

class CouponService extends BaseService
{
    public function getUsableCoupons($userId)
    {
        return CouponUser::query()->where('user_id', $userId)
            ->where('status', CouponUserEnums::STATUS_USABLE)
            ->get();
    }

    public function getCouponUser($id, $columns = ['*'])
    {
        return CouponUser::query()->find($id, $columns);
    }

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

    /**
     * 验证当前价格是否可以使用这张优惠券
     * @param Coupon $coupon
     * @param CouponUser $couponUser
     * @param double $price
     */
    public function checkCouponAndPrice($coupon, $couponUser, $price)
    {
        if (empty($couponUser)) {
            return false;
        }
        if (empty($coupon)) {
            return false;
        }
        if ($couponUser->coupon_id != $coupon->id) {
            return false;
        }
        if ($coupon->status != CouponEnums::STATUS_NORMAL) {
            return false;
        }
        if ($coupon->goods_type != CouponEnums::GOODS_TYPE_ALL) {
            return false;
        }
        if (bccomp($coupon->min, $price) == 1) {
            return false;
        }
        $now = now();
        switch ($coupon->time_type) {
            case CouponEnums::TIME_TYPE_TIME:
                $start = Carbon::parse($coupon->start_time);
                $end = Carbon::parse($coupon->end_time);
                if ($now->isBefore($start) ||$now->isAfter($end)) {
                    return false;
                }
                break;
            case CouponEnums::TIME_TYPE_DAYS:
                $expired = Carbon::parse($couponUser->add_time)->addDays($coupon->days);
                if ($now->isAfter($expired)) {
                    return false;
                }
                break;
            default:
                return false;
        }
        return true;
    }

    public function getMeetPriceCouponAndSort($userId, $price)
    {
        $couponUsers = CouponService::getInstance()->getUsableCoupons($userId);
        $couponIds = $couponUsers->pluck('coupon_id')->toArray();
        $coupons = CouponService::getInstance()->getCoupons($couponIds)->keyBy('id');
        return $couponUsers->filter(function (CouponUser $couponUser) use ($coupons, $price) {
            /**@var Coupon $coupon */
            $coupon = $coupons->get($couponUser->coupon_id);
            return CouponService::getInstance()->checkCouponAndPrice($coupon, $couponUser, $price);
        })->sortByDesc(function (CouponUser $couponUser) use ($coupons) {
            $coupon = $coupons->get($couponUser->coupon_id);
            return $coupon->discount;
        });
    }

    public function getCouponUserByCouponId($userId, $couponId)
    {
        return CouponUser::query()->where('user_id', $userId)->Where('coupon_id', $couponId)
            ->orderBy('id')->first();
    }

    public function getMostMeetPriceCoupon($userId, $couponId, $price, &$availableCouponLength = 0)
    {
        $couponUsers = $this->getMeetPriceCouponAndSort($userId, $price);
        $availableCouponLength = $couponUsers->count();

        if (is_null ($couponId) || $couponId == -1) {
            return null;
        }

        if (!empty($couponId)) {
            $coupon = $this->getCoupon($couponId);
            $couponUser = $this->getCouponUserByCouponId($userId, $couponId);
            $is = $this->checkCouponAndPrice($coupon, $couponUser, $price);
            if ($is) {
                return $couponUser;
            }
        }

        return $couponUsers->first();
    }

}