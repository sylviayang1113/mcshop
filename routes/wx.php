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
Route::post('address/list', 'AddressController@list'); // 收货地址
Route::post('address/detail', 'AddressController@detail'); // 收货地址详情
Route::post('address/save', 'AddressController@save'); // 保存收货地址
Route::post('address/delete', 'AddressController@delete'); // 删除收货地址

# 商品模块-类目
Route::get('catalog/delete', 'CatalogController@index'); // 分类目录全部分类数据接口
Route::get('catalog/delete', 'CatalogController@current'); // 分类目录当前分类数据接口

# 商品模块-品牌
Route::get('brand/list', 'BrandController@list'); // 品牌列表
Route::get('brand/detail', 'BrandController@detail'); // 品牌详情

# 商品模块-商品
Route::get('goods/count', 'GoodsController@detail'); // 统计商品总数
Route::get('goods/category', 'GoodsController@category'); // 根据分类获取商品列表数据
Route::get('goods/list', 'GoodsController@list'); // 获得商品列表
Route::get('goods/detail', 'GoodsController@detail'); // 获得商品详情

# 营销模块-优惠券
Route::get('coupon/list', 'CouponController@list'); //优惠券列表
Route::get('coupon/myList', 'CouponController@myList'); //我的优惠券列表
#Route::any('coupon/selectList', ''); //当前订单可用优惠券列表
Route::post('coupon/receive', 'CouponController@receive'); //优惠券领取

# 营销模块-团购
Route::get('groupon/list', 'GrouponController@list'); // 团购列表

Route::get('home/redirectShareUrl', 'HomeController@redirectShareUrl')->name('home.redirectShareUrl');
