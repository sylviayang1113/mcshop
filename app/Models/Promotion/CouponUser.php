<?php


namespace App\Models\Promotion;


use App\Models\BaseModel;

class CouponUser extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'coupon_id',
        'user_id',
        'start_time',
        'end_time'
    ];

}