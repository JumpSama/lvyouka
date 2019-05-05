<?php
/**
 * Author: JumpSama
 * Date: 2019/5/5
 * Time: 14:00
 */

namespace App\Api\Controllers;


use App\Distribution;
use App\DistributionFlow;
use App\Withdraw;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DistributionController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 分销二维码
     * @return mixed
     */
    public function distributionQrcode()
    {
        $user = JWTAuth::user();

        $result = Distribution::getQrCode(Distribution::MAIN_USER, $user['id']);

        return $this->responseData($result);
    }

    /**
     * 分销金额
     * @return mixed
     */
    public function distributionAmount()
    {
        $user = JWTAuth::user();

        $amount = Distribution::getAmount(Distribution::MAIN_USER, $user['id']);

        return $this->responseData([
            'amount' => $amount
        ]);
    }

    /**
     * 分销记录
     * @param Request $request
     * @return mixed
     */
    public function distributionList(Request $request)
    {
        $data = $request->only(['start_time', 'end_time']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $user = JWTAuth::user();

        $list = DistributionFlow::getList($data, DistributionFlow::MAIN_USER, $user['id'], $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 提现
     * @return mixed
     */
    public function withdraw()
    {
        $user = JWTAuth::user();

        $result = Distribution::withdraw(Distribution::MAIN_USER, $user['id']);

        if ($result === true) return $this->responseData();

        return $this->responseError([], $result);
    }

    /**
     * 当前用户提现记录
     * @param Request $request
     * @return mixed
     */
    public function withdrawMy(Request $request)
    {
        $data = $request->only(['status', 'start_time', 'end_time']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $user = JWTAuth::user();

        $list = Withdraw::userList($data, $offset, $limit, $user['id']);

        return $this->responseData($list);
    }

    /**
     * 提现记录
     * @param Request $request
     * @return mixed
     */
    public function withdrawList(Request $request)
    {
        $data = $request->only(['status', 'start_time', 'end_time']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Withdraw::userList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 提现审核操作
     * @param Request $request
     * @return mixed
     */
    public function withdrawOperate(Request $request)
    {
        if (!$request->filled(['id', 'type'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        if (!Withdraw::operate($request->input('id'), $request->input('type'), $user['id'])) return $this->responseError();

        return $this->responseData();
    }
}