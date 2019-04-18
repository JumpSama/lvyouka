<?php

namespace App;

use App\Http\Controllers\PayController;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TempMember extends Model
{
    const STATUS_NOT = 0;      //未支付
    const STATUS_WAIT = 1;      //等待审核
    const STATUS_REFUSE = 2;    //已拒绝

    /**
     * 审核列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function approveList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('temp_members as a')
            ->select(['a.id', 'a.status', 'a.name', 'a.sex', 'a.phone', 'a.identity', 'a.identity_front', 'a.identity_reverse', 'b.id as member_id'])
            ->leftJoin('members as b', 'a.identity', '=', 'b.identity');

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['name'])) $sql = $sql->where('a.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['phone'])) $sql = $sql->where('a.phone', 'like', '%' . $data['phone'] . '%');
        if (isset($data['identity'])) $sql = $sql->where('a.identity', 'like', '%' . $data['identity'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 审核操作
     * @param $id
     * @param $type
     * @param $userId
     * @return bool
     */
    static public function approveOperate($id, $type, $userId)
    {
        DB::beginTransaction();

        try {
            $detail = self::find($id);
            $member = Member::where('identity', $detail->identity)->first();

            if ($type == 'approve') {
                // 没有实体卡的用户
                if (!$member) {
                    $member = new Member();

                    $member->created_by = $userId;
                    $member->name = $detail->name;
                    $member->sex = $detail->sex;
                    $member->phone = $detail->phone;
                    $member->identity = $detail->identity;
                    $member->status = Member::STATUS_NORMAL;
                    $member->overdue = Carbon::now()->addDays(365)->toDateString();
                }

                // 同步公众号端数据
                $member->openid = $detail->openid;
                $member->identity_front = $detail->identity_front;
                $member->identity_reverse = $detail->identity_reverse;
                $member->updated_by = $userId;

                $member->save();

                // 日志
                Log::add($userId, '审核通过用户-' . $detail->name . '(' . $detail->identity . ')');

                // 删除临时表
                self::destroy($id);
            } else {
                $detail->status = self::STATUS_REFUSE;

                // 退款
                if (!$member) {
                    // TODO 退款
                }

                $detail->save();

                // 日志
                Log::add($userId, '审核拒绝用户-' . $detail->name . '(' . $detail->identity . ')');
            }

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 添加记录
     * @param $data
     * @param $openid
     * @return array
     */
    static public function add($data, $openid)
    {
        $identity = $data['identity'];

        $unique = self::checkUnique($identity, $data['phone'], $openid);

        if ($unique !== false) {
            return [
                'flag' => false,
                'msg' => $unique
            ];
        }

        DB::beginTransaction();

        try {
            $member = self::where('openid', $openid)->first();

            if ($member) {
                $sql = $member;
            } else {
                $sql = new self;

                $sql->openid = $openid;
            }

            $sql->name = $data['name'];
            $sql->sex = $data['sex'];
            $sql->phone = $data['phone'];
            $sql->identity = $data['identity'];
            $sql->identity_front = $data['identity_front'];
            $sql->identity_reverse = $data['identity_reverse'];

            // 判断用户是否需要支付
            $pay = Member::where('identity', $identity)->whereNull('openid')->count() > 0;

            // 不用支付
            if ($pay) {
                if (!Member::checkParams($data)) throw new \Exception('与实体卡录入信息不符');

                $sql->status = self::STATUS_WAIT;

                $sql->save();

                DB::commit();

                return [ 'flag' => true];
            } else {
                // 支付
                $out_trade_no = str_random(20) . '-' . time();

                $sql->status = self::STATUS_NOT;
                $sql->out_trade_no = $out_trade_no;

                $sql->save();

                CardOrder::add($data['identity'], $out_trade_no);

                $pay = new PayController();
                $payParams = $pay->cardPay($out_trade_no, $openid);

                if ($payParams === false) throw new \Exception('调用支付失败');

                DB::commit();

                return [
                    'flag' => true,
                    'params' => $payParams
                ];
            }
        } catch (\Exception $exception) {
            DB::rollBack();

            return [
                'flag' => false,
                'msg' => $exception->getMessage()
            ];
        }
    }

    /**
     * 未支付重新支付
     * @param $id
     * @param $openid
     * @return array
     */
    static public function repay($id, $openid)
    {
        $member = self::find($id);

        if (!$member || $member->openid != $openid) {
            return [
                'flag' => false,
                'msg' => '订单不存在'
            ];
        }

        if ($member->status != 0) {
            return [
                'flag' => false,
                'msg' => '订单已支付'
            ];
        }

        $order = CardOrder::where('out_trade_no', $member->out_trade_no)->first();

        $pay = new PayController();
        $payParams = $pay->getParams($member->out_trade_no, $order->amount, $order->prepay_id);

        return [
            'flag' => true,
            'params' => $payParams
        ];
    }

    /**
     * 获取用户已提交的资料
     * @param $openid
     * @return mixed
     */
    static public function get($openid)
    {
        return self::where('openid', $openid)->first();
    }

    /**
     * 检查唯一性
     * @param $identity
     * @param $phone
     * @param $openid
     * @return bool|string
     */
    static public function checkUnique($identity, $phone, $openid)
    {
        $identityCount = self::where('identity', $identity)->where('openid', '<>', $openid)->count();

        if ($identityCount > 0) {
            return '身份证已存在';
        }

        $phoneCount = self::where('phone', $phone)->where('openid', '<>', $openid)->count();

        if ($phoneCount > 0) {
            return '手机号已存在';
        }

        $memberCount = Member::where('identity', $identity)->whereNotNull('openid')->count();

        if ($memberCount > 0) {
            return '身份证已存在';
        }

        return false;
    }
}
