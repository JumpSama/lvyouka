<?php
/**
 * Author: JumpSama
 * Date: 2019/4/11
 * Time: 14:51
 */

namespace App\Api\Controllers;


use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 用户列表
     * @param Request $request
     * @return mixed
     */
    public function userList(Request $request)
    {
        $data = $request->only(['name', 'account']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = User::userList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 用户保存
     * @param Request $request
     * @return mixed
     */
    public function userStore(Request $request)
    {
        if (!$request->has(['name', 'account', 'role'])) return $this->responseError([], '参数错误');

        $data = $request->only(['id', 'name', 'account', 'role']);

        $user = JWTAuth::user();

        if (User::userStore($data, $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 用户详情
     * @param Request $request
     * @return mixed
     */
    public function userDetail(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $detail = User::find($request->input('id'));

        return $this->responseData($detail);
    }

    /**
     * 用户删除
     * @param Request $request
     * @return mixed
     */
    public function userDel(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (User::userDel($request->input('id'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 用户操作
     * @param Request $request
     * @return mixed
     */
    public function userOperate(Request $request)
    {
        if (!$request->has(['id', 'type'])) return $this->responseError([], '参数错误');

        $data = $request->only(['id', 'type']);

        $user = JWTAuth::user();

        if (User::userOperate($data, $user['id'])) return $this->responseData();

        return $this->responseError();
    }
}