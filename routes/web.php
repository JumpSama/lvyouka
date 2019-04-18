<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('wechat')->group(function () {
    // 图片上传
    Route::post('upload', 'WechatController@upload');
    // 商品详情
    Route::get('detail', 'WechatController@detail');
    // 用户注册
    Route::get('login', 'WechatController@login');
    // 商品列表
    Route::get('mall', 'WechatController@mall');
    // 我的主页
    Route::get('me', 'WechatController@me');
    // 我的订单
    Route::get('order', 'WechatController@order');
    // 我的积分
    Route::get('point', 'WechatController@point');
    // 签到
    Route::get('sign', 'WechatController@sign');

    // 用户信息录入
    Route::post('apply', 'WechatController@apply');
    // 未支付重新支付
    Route::post('repay', 'WechatController@repay');
    // 支付回调
    Route::any('card_callback', 'PayController@cardCallback');
    // 签到
    Route::post('sign_in', 'WechatController@signIn');
    // 积分列表
    Route::post('point_list', 'WechatController@pointList');
    // 商品列表
    Route::post('commodity_list', 'WechatController@commodityList');
    // 商品购买
    Route::post('commodity_buy', 'WechatController@commodityBuy');
    // 订单列表
    Route::post('order_list', 'WechatController@orderList');
});
