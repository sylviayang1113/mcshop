<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Http\Controllers\Controller;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 获取参数
        $username = $request->input('username');
        $password = $request->input('password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');

        // 验证参数是否为空
        if (empty($username) || empty($password) || empty($mobile) || empty($code)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }


        // 验证用户是否存在
        $user = (new Service())->getByUsername($username);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_NAME_REGISTERED);
        }

        $validator = Validator::make(['mobile' => $mobile], ['mobile' =>'regex:/^1[0-9]{10}$']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        $user = (new Service())->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }

        //  验证验证码是否正确
        UserService::getInstance()->checkCaptcha($mobile, $code);

        // 写入用户表
        $user = new User();
        $user->username = $username;
        $user->password = Hash::make($password);
        $user->mobile = $mobile;
        $user->avatar = "";
        $user->nickname = $username;
        $user->last_login_time = Carbon::now()->toDateTimeString();//'Y-m-d H:i:s'
        $user->last_login_ip = $request->getClientIp();
        $user->save();

        // todo 新用户发全券
        // todo token
        return $this->success( [
                    'token' => '',
                    'userInfo' => [
                        'nickname' => $username,
                        'avatarUrl' => $user->avatar
                    ]
        ]);

    }

    public function regCaptcha(Request $request)
    {
        // 获取手机号
        $mobile = $request->input('mobile');

        //　验证手机号是否合法
        if (empty($mobile)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' =>'regex:/^1[0-9]{10}$']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        // 验证手机号是否已经被注册
        $user = (new Service())->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }

        // todo 防刷验证, 一分钟内只能请求一次， 当天只能请求一次
        $lock = Cache::add('register_captcha_lock_'.$mobile, 1, 60);
        if (!$lock) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY);
        }

        $isPass = (new UserService())->checkMobileSendCaptchaCount($mobile);
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码当天发送不能超过10次');

        }

        $code = (new UserService())->setCaptcha($mobile);
        (new UserService())->sendCaptchaMsg($mobile, $code);
        return $this->success();
    }


}