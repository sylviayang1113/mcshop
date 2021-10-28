<?php


namespace App\Models;


class Comment extends BaseModel
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'pic_urls' => 'array'
    ];

}