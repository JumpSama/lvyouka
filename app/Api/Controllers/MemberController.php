<?php
/**
 * Author: JumpSama
 * Date: 2019/4/12
 * Time: 10:48
 */

namespace App\Api\Controllers;


use App\CardUsedRecord;
use App\Member;
use App\SmsRecord;
use App\TempMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * 实体卡开卡
     * @param Request $request
     * @return mixed
     */
    public function cardToMember(Request $request)
    {
        if (!$request->filled(['card_number', 'name', 'sex', 'phone', 'avatar', 'identity', 'code'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        $data = $request->only(['card_number', 'name', 'sex', 'phone', 'avatar', 'identity', 'code']);

        $result = Member::add($data, $user['id']);

        if ($result !== true) return $this->responseError([], $result);

        return $this->responseData();
    }

    /**
     * 挂失绑定新卡
     * @param Request $request
     * @return mixed
     */
    public function cardChangeMember(Request $request)
    {
        if (!$request->filled(['card_number', 'identity'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        $data = $request->only(['card_number', 'identity']);

        $result = Member::change($data, $user['id']);

        if ($result !== true) return $this->responseError([], $result);

        return $this->responseData();
    }

    /**
     * 实体卡绑定用户
     * @param Request $request
     * @return mixed
     */
    public function cardBindMember(Request $request)
    {
        if (!$request->filled(['card_number', 'identity', 'avatar'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        $data = $request->only(['card_number', 'identity', 'avatar']);

        $result = Member::bind($data, $user['id']);

        if ($result !== true) return $this->responseError([], $result);

        return $this->responseData();
    }

    /**
     * 获取用户使用记录
     * @param Request $request
     * @return mixed
     */
    public function cardUseLog(Request $request)
    {
        if (!$request->filled('card_number') && !$request->filled('qrcode')) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        $data = $request->only(['card_number', 'qrcode']);

        $list = CardUsedRecord::getLog($data, $user['id']);

        if (isset($list['msg'])) return $this->responseError([], $list['msg']);

        return $this->responseData($list);
    }

    /**
     * 刷卡记录添加
     * @param Request $request
     * @return mixed
     */
    public function cardUseLogAdd(Request $request)
    {
        if (!$request->filled(['member_id', 'item_id'])) return $this->responseError([], '参数错误');

        $user = JWTAuth::user();

        $result = CardUsedRecord::add($request->input('member_id'), $user['id'], $request->input('item_id'));

        if ($result !== true) return $this->responseError([], $result);

        return $this->responseData();
    }

    /**
     * 发送短信
     * @param Request $request
     * @return mixed
     */
    public function sendSms(Request $request)
    {
        if (!$request->filled('phone')) return $this->responseError([], '参数错误');

        $sms = SmsRecord::sendCode($request->input('phone'));

        if ($sms !== true) return $this->responseError();

        return $this->responseData();
    }

    /**
     * 刷卡获取会员详情
     * @param Request $request
     * @return mixed
     */
    public function memberDetailByCard(Request $request)
    {
        if (!$request->filled('card_number')) return $this->responseError([],'参数错误');

        $detail = Member::memberDetailByCard($request->input('card_number'));

        if (isset($detail['msg'])) return $this->responseError([], $detail['msg']);

        return $this->responseData($detail);
    }

    /**
     * 续费
     * @param Request $request
     * @return mixed
     */
    public function cardRenewMember(Request $request)
    {
        if (!$request->filled('card_number')) return $this->responseError([],'参数错误');

        $user = JWTAuth::user();

        $result = Member::renew($request->input('card_number'), $user['id']);

        if ($result !== true) return $this->responseError([], $result);

        return $this->responseData();
    }

    /**
     * 实体卡冻结
     * @param Request $request
     * @return mixed
     */
    public function memberDisable(Request $request)
    {
        if (!$request->filled('id')) return $this->responseError([],'参数错误');

        $user = JWTAuth::user();

        if (Member::disableCard($request->input('id'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 解冻实体卡
     * @param Request $request
     * @return mixed
     */
    public function memberEnable(Request $request)
    {
        if (!$request->filled('id')) return $this->responseError([],'参数错误');

        $user = JWTAuth::user();

        if (Member::enableCard($request->input('id'), $user['id'])) return $this->responseData();

        return $this->responseError();
    }
}