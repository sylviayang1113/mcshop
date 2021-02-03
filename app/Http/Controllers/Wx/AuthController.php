<?php

namespace App\Http\Controllers\Wx;

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
            return ['errno' => 401, 'errmsg' => '参数不对'];
        }


        // 验证用户是否存在
        $user = (new Service())->getByUsername($username);
        if (!is_null($user)) {
            return ['errno' => 704, 'errmsg' => '用户名已注册'];
        }

        $validator = Validator::make(['mobile' => $mobile], ['mobile' =>'regex:/^1[0-9]{10}$']);
        if ($validator->fails()) {
            return ['errno' => 707, 'errmsg' => '手机格式不正确'];
        }
        $user = (new Service())->getByMobile($mobile);
        if (!is_null($user)) {
            return ['errno' => 705, 'errmsg' => '手机号已注册'];
        }

        //  验证验证码是否正确
        $isPass = (new UserService())->checkCaptcha($mobile, $code);
        if (!$isPass) {
            return ['errno' => 703, 'errmsg' => '验证码错误'];
        }
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
        return ['errno' => 0, 'errmsg' => '成功', 'data' => [
                    'token' => '',
                    'userInfo' => [
                        'nickname' => $username,
                        'avatarUrl' => $user->avatar
                    ]
             ]
        ];
    }

    public function regCaptcha(Request $request)
    {
        // 获取手机号
        $mobile = $request->input('mobile');

        //　验证手机号是否合法
        if (empty($mobile)) {
            return ['errno' => 401, 'errmsg' => '参数不对'];
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' =>'regex:/^1[0-9]{10}$']);
        if ($validator->fails()) {
            return ['errno' => 707, 'errmsg' => '手机格式不正确'];
        }
        // 验证手机号是否已经被注册
        $user = (new Service())->getByMobile($mobile);
        if (!is_null($user)) {
            return ['errno' => 705, 'errmsg' => '手机号已注册'];
        }

        // todo 防刷验证, 一分钟内只能请求一次， 当天只能请求一次
        $lock = Cache::add('register_captcha_lock_'.$mobile, 1, 60);
        if (!$lock) {
            return ['errno' => 702, 'errmsg' => '验证码未超时1分钟，不能发送'];
        }

        $isPass = (new UserService())->checkMobileSendCaptchaCount($mobile);
        if (!$isPass) {
            return ['errno' => 702, 'errmsg' => '验证码当天发送不能超过10次'];

        }

        $code = (new UserService())->setCaptcha($mobile);
        (new UserService())->sendCaptchaMsg($mobile, $code);
        return ['errno' => 0, 'errmsg' => '成功', 'data' => null];
    }


}
