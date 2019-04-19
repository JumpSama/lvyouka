<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

header('Access-Control-Allow-Origin: ' . config('app.admin_domain', '*'));
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
header('Access-Control-Allow-Headers: Origin, Content-Type, Cookie, Accept, Authorization, X-CSRF-TOKEN, X-XSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['namespace' => 'App\Api\Controllers'], function ($api) {
    // 后台路由
    $api->group(['prefix' => 'admin'], function ($api) {
        // 图片上传
        $api->post('upload/image', 'FileController@imageUpload');

        // 登录
        $api->post('login', 'AuthController@authenticateAdmin');
        // 登出
        $api->get('logout', 'AuthController@logout');
        // 修改密码
        $api->post('password', 'AuthController@password');
        // 登录用户信息
        $api->post('me', 'AuthController@getUser');
        // 首页数字
        $api->post('home_count', 'AuthController@homeCount');

        // 用户列表
        $api->post('user_list', 'UserController@userList');
        // 用户保存
        $api->post('user_store', 'UserController@userStore');
        // 用户详情
        $api->post('user_detail', 'UserController@userDetail');
        // 用户删除
        $api->post('user_del', 'UserController@userDel');
        // 用户操作
        $api->post('user_operate', 'UserController@userOperate');

        // 角色列表
        $api->post('role_list', 'RoleController@roleList');
        // 角色全部
        $api->post('role_all', 'RoleController@roleAll');
        // 角色保存
        $api->post('role_store', 'RoleController@roleStore');
        // 角色详情
        $api->post('role_detail', 'RoleController@roleDetail');
        // 角色删除
        $api->post('role_del', 'RoleController@roleDel');
        // 菜单全部
        $api->post('menu_all', 'RoleController@menuAll');

        // 场所列表
        $api->post('site_list', 'SiteController@siteList');
        // 场所全部
        $api->post('site_all', 'SiteController@siteAll');
        // 场所保存
        $api->post('site_store', 'SiteController@siteStore');
        // 场所详情
        $api->post('site_detail', 'SiteController@siteDetail');
        // 场所删除
        $api->post('site_del', 'SiteController@siteDel');


        // 会员列表
        $api->post('member_list', 'MemberController@memberList');
        // 会员详情
        $api->post('member_detail', 'MemberController@memberDetail');
        // 审核列表
        $api->post('approve_list', 'MemberController@approveList');
        // 审核操作
        $api->post('approve_operate', 'MemberController@approveOperate');
        // 卡片列表
        $api->post('card_list', 'CardController@cardList');

        // 商品列表
        $api->post('commodity_list', 'ShopController@commodityList');
        // 商品保存
        $api->post('commodity_store', 'ShopController@commodityStore');
        // 商品详情
        $api->post('commodity_detail', 'ShopController@commodityDetail');
        // 商品删除
        $api->post('commodity_del', 'ShopController@commodityDel');
        // 商品操作
        $api->post('commodity_operate', 'ShopController@commodityOperate');
        // 订单列表
        $api->post('order_list', 'ShopController@orderList');
        // 订单操作
        $api->post('order_operate', 'ShopController@orderOperate');

        // 获取配置
        $api->post('get_config', 'ConfigController@getConfig');
        // 更新配置
        $api->post('set_config', 'ConfigController@setConfig');
        // 日志列表
        $api->post('log_list', 'LogController@logList');

        // 卡片录入
        $api->post('card_add', 'CardController@cardAdd');
        // 开卡
        $api->post('card_to_member', 'MemberController@cardToMember');
        // 绑卡
        $api->post('card_bind_member', 'MemberController@cardBindMember');
        // 卡片使用记录
        $api->post('card_use_log', 'MemberController@cardUseLog');
    });
});
