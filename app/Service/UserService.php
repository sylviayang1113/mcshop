<?php

namespace App\Service;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class UserService extends BaseService
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

    public function checkMobileSendCaptchaCount(String $mobile)
    {
        $countKey = 'register_captcha_count_'.$mobile;
        if (Cache::has($countKey)) {
            $count = Cache::increment('register_captcha_count_'.$mobile);
            if ($count > 10) {
                return false;
            }
        } else {
            Cache::put($countKey, 1, Carbon::tomorrow()->diffInSeconds(now()));
        }
        return true;
    }

    public function sendCaptchaMsg(string $mobile, string $code)
    {
        if (app()->environment('testing')) {
            return;
        }
        // 发送短信验证码
        Notification::route(
            EasySmsChannel::class,
            new PhoneNumber($mobile, 86)
        )->notify(new VerificationCode($code));
    }

    // 验证短信验证码
    public function checkCaptcha(string $mobile, string $code)
    {
        if (!app()->environment('production')) {
            return true;
        }
        $key = 'register_captcha_'.$mobile;
        $isPass =  $code === Cache::get($key);
        if ($isPass)
        {
            Cache::forget($key);

            return true;
        } else {
            throw new BusinessException(CodeResponse::AUTH_CAPTCHA_UNMATCH);
        }

    }

    // 设置手机短信验证码
    public function setCaptcha(string $mobile)
    {
        // 随机生成6为验证吗
        $code = random_int(100000, 999999);
        $code = strval();
        // 保存手机号和验证码的关系
        Cache::put('register_captcha_'.$mobile, $code, 600);
        return $code;
    }
}
