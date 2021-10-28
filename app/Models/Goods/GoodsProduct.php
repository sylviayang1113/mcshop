<?php


namespace App\Models\Goods;


use App\Models\BaseModel;

class GoodsProduct extends BaseModel
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'specifications' => 'array',
        'price' => 'float'
    ];
}