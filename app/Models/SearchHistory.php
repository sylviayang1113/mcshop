<?php


namespace App\Models;


class SearchHistory extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'keyword',
        'from'
    ];
}