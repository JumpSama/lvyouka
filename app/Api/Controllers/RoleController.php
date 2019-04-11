<?php
/**
 * Author: JumpSama
 * Date: 2019/4/11
 * Time: 10:19
 */

namespace App\Api\Controllers;


use App\Menu;
use App\Role;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 角色列表
     * @param Request $request
     * @return mixed
     */
    public function roleList(Request $request)
    {
        $data = $request->only(['name']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Role::roleList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 全部角色
     * @return mixed
     */
    public function roleAll()
    {
        return $this->responseData(Role::roleAll());
    }

    /**
     * 角色保存
     * @param Request $request
     * @return mixed
     */
    public function roleStore(Request $request)
    {
        if (!$request->has(['name', 'ids'])) return $this->responseError([], '参数错误');

        $data = $request->only(['id', 'name', 'ids']);

        $user = JWTAuth::user();

        if (Role::roleStore($data, $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 角色详情
     * @param Request $request
     * @return mixed
     */
    public function roleDetail(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $detail = Role::roleDetail($request->input('id'));

        return $this->responseData($detail);
    }

    /**
     * 角色删除
     * @param Request $request
     * @return mixed
     */
    public function roleDel(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (Role::roleDel($request->input('id'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 全部菜单
     * @return mixed
     */
    public function menuAll()
    {
        $list = Menu::pluck('name', 'id');
        return$this->responseData($list);
    }
}