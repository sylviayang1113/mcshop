<?php


namespace App\Models\Goods;


use App\Models\BaseModel;
use App\Models\BooleanSoftDeletes;

class Goods extends BaseModel
{
    use BooleanSoftDeletes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'counter_price' => 'float',
        'retail_price' => 'float',
        'is_new' => 'boolean',
        'is_hot'=> 'boolean',
        'gallery' => 'array',
        'is_on_sale' => 'boolean'
    ];
}