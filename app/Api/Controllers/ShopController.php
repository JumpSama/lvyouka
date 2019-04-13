<?php
/**
 * Author: JumpSama
 * Date: 2019/4/13
 * Time: 14:02
 */

namespace App\Api\Controllers;


use App\Commodity;
use App\Order;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShopController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 商品列表
     * @param Request $request
     * @return mixed
     */
    public function commodityList(Request $request)
    {
        $data = $request->only(['status', 'name']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Commodity::commodityList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 商品保存
     * @param Request $request
     * @return mixed
     */
    public function commodityStore(Request $request)
    {
        if (!$request->has(['name', 'price', 'status', 'image', 'banner', 'content', 'stock'])) return $this->responseError([], '参数错误');

        $data = $request->only(['id', 'name', 'price', 'status', 'image', 'banner', 'content', 'stock']);

        $user = JWTAuth::user();

        if (Commodity::commodityStore($data, $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 商品详情
     * @param Request $request
     * @return mixed
     */
    public function commodityDetail(Request $request)
    {
        if(!$request->filled('id')) return $this->responseError([], '参数错误');

        $detail = Commodity::commodityDetail($request->input('id'));

        return $this->responseData($detail);
    }

    /**
     * 商品删除
     * @param Request $request
     * @return mixed
     */
    public function commodityDel(Request $request)
    {
        if (!$request->filled(['id'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (Commodity::commodityDel($request->input('id'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 商品操作
     * @param Request $request
     * @return mixed
     */
    public function commodityOperate(Request $request)
    {
        if (!$request->has(['id', 'type'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (Commodity::commodityOperate($request->input('id'), $request->input('type'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function orderList(Request $request)
    {
        $data = $request->only(['status', 'name', 'keyword']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Order::orderList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 订单操作
     * @param Request $request
     * @return mixed
     */
    public function orderOperate(Request $request)
    {
        if (!$request->has(['id', 'type'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (Order::orderOperate($request->input('id'), $request->input('type'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }
}