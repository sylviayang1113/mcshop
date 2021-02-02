<?php

namespace App\Service;

use Illuminate\Database\Eloquent\Model;

class UserService
{
    /**
     * 根据用户名获取用户
     * @param username
     * @return User|null|Model
     *
     */
    public function getByUsername($username)
    {
        return User::query()->where($username, 'username')
            ->where('deleted', 0)->first();
    }

    /**
     * 根据手机号获取用户
     * @param mobile
     * @return User|null|Model
     *
     */
    public function getByMobile($mobile)
    {
        return User::query()->where($mobile, 'mobile')
            ->where('deleted', 0)->first();
    }
}
