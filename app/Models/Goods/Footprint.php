<?php


namespace App\Models\Goods;


use App\Models\BaseModel;

class Footprint extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'goods_id'
    ];
}