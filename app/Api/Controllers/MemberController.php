<?php
/**
 * Author: JumpSama
 * Date: 2019/4/12
 * Time: 10:48
 */

namespace App\Api\Controllers;


use App\Member;
use App\TempMember;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class MemberController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 会员列表
     * @param Request $request
     * @return mixed
     */
    public function memberList(Request $request)
    {
        $data = $request->only(['status', 'name', 'phone', 'identity', 'number']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Member::memberList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 会员详情
     * @param Request $request
     * @return mixed
     */
    public function memberDetail(Request $request)
    {
        if(!$request->filled('id') && !$request->filled('identity')) return $this->responseError([], '参数错误');

        $data = $request->only(['id', 'identity']);

        $detail = Member::memberDetail($data);

        return $this->responseData($detail);
    }

    /**
     * 审核列表
     * @param Request $request
     * @return mixed
     */
    public function approveList(Request $request)
    {
        $data = $request->only(['status', 'name', 'phone', 'identity']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = TempMember::approveList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 审核操作
     * @param Request $request
     * @return mixed
     */
    public function approveOperate(Request $request)
    {
        if(!$request->filled(['id', 'type'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (TempMember::approveOperate($request->input('id'), $request->input('type'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }
}