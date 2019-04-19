<?php
/**
 * Author: JumpSama
 * Date: 2019/4/19
 * Time: 14:14
 */

namespace App\Api\Controllers;


use App\Site;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class SiteController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 场所列表
     * @param Request $request
     * @return mixed
     */
    public function siteList(Request $request)
    {
        $data = $request->only(['name']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Site::siteList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 全部场所
     * @return mixed
     */
    public function siteAll()
    {
        return $this->responseData(Site::siteAll());
    }

    /**
     * 场所保存
     * @param Request $request
     * @return mixed
     */
    public function siteStore(Request $request)
    {
        if (!$request->has(['name', 'items'])) return $this->responseError([], '参数错误');

        $data = $request->only(['id', 'name', 'items']);

        $user = JWTAuth::user();

        if (Site::siteStore($data, $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 场所详情
     * @param Request $request
     * @return mixed
     */
    public function siteDetail(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $detail = Site::siteDetail($request->input('id'));

        return $this->responseData($detail);
    }

    /**
     * 场所删除
     * @param Request $request
     * @return mixed
     */
    public function siteDel(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (Site::siteDel($request->input('id'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }
}