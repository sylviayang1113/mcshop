<?php


namespace App\Models\Promotion;


use App\Models\BaseModel;

class Coupon extends BaseModel
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'discount' => 'float',
        'min' => 'float'
    ];
}