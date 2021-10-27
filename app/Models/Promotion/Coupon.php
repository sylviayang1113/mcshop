<?php


namespace App\Models\Promotion;


use App\Models\BaseModel;

class Coupon extends BaseModel
{

    protected $table = 'coupon';

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

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'deleted' => 'boolean',
        'discount' => 'float',
        'min' => 'float'
    ];
}