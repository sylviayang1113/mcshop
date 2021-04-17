<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
# 用户模块-用户
Route::post('auth/register', 'AuthController@register'); // 账号注册
Route::post('auth/regCaptcha', 'AuthController@regCaptcha'); // 注册验证码
Route::post('auth/login', 'AuthController@login'); // 账号登录
Route::get('auth/info', 'AuthController@info'); // 用户信息
Route::post('auth/logout', 'AuthController@logout'); //账号登出
Route::post('auth/reset', 'AuthController@lreset'); //账号密码重置
Route::post('auth/captcha', 'AuthController@captacha'); // 验证码
Route::post('auth/profile', 'AuthController@profile'); // 账号修改


# 用户模块-地址
Route::any('auth/list', 'AddressController@list'); // 收货地址
Route::any('auth/detail', 'AddressController@detail'); // 收货地址详情
Route::any('auth/save', 'AddressController@save'); // 保存收货地址
Route::any('auth/delete', 'AddressController@delete'); // 删除收货地址