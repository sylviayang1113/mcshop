<?php

namespace App\Models\User;

use App\Models\BaseModel;

class Address extends BaseModel
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_default' => 'boolean'
    ];

}
