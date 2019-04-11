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

        // 日志列表
        $api->post('log_list', 'LogController@logList');
    });
});
