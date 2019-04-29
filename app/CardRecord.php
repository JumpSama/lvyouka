<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CardRecord extends Model
{
    const TYPE_NEW = 1; // 开卡
    const TYPE_RENEW = 2;  // 续费

    const PAY_OFFLINE = 1;  // 线下付款
    const PAY_ONLINE = 2;    // 线上付款

    /**
     * 开卡、续费记录添加
     * @param $memberId
     * @param $overdue
     * @param $type
     * @param $payType
     * @param int $userId
     * @return bool
     */
    static public function add($memberId, $overdue, $type, $payType, $userId = 0)
    {
        $sql = new self;

        $sql->member_id = $memberId;
        $sql->overdue = $overdue;
        $sql->type = $type;
        $sql->pay_type = $payType;
        $sql->user_id = $userId;

        return $sql->save();
    }

    /**
     * 开卡、续费详情
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function recordList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('card_records as a')
            ->select(['a.created_at', 'a.user_id', 'a.member_id', 'a.type', 'a.pay_type', 'b.name as user_name', 'c.name', 'c.phone', 'c.identity', 'd.number'])
            ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
            ->leftJoin('members as c', 'a.member_id', '=', 'c.id')
            ->leftJoin('cards as d', 'c.card_id', '=', 'd.id');

        if (isset($data['type'])) $sql = $sql->where('a.type', $data['type']);
        if (isset($data['pay_type'])) $sql = $sql->where('a.pay_type', $data['pay_type']);
        if (isset($data['user_keyword'])) {
            $userKeyword = $data['user_keyword'];
            $sql = $sql->where(function($q) use ($userKeyword) {
                $q->where('a.user_id', $userKeyword)
                    ->orWhere('b.name', 'like', '%'.$userKeyword.'%');
            });
        }
        if (isset($data['member_keyword'])) {
            $memberKeyword = $data['member_keyword'];
            $sql = $sql->where(function($q) use ($memberKeyword) {
                $q->where('c.name', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('c.phone', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('c.identity', 'like', '%'.$memberKeyword.'%');
            });
        }
        if (isset($data['start_time'])) $sql = $sql->where('a.created_at', '>=', $data['start_time'] . ' 00:00:00');
        if (isset($data['end_time'])) $sql = $sql->where('a.created_at', '<=', $data['end_time']. ' 23:59:59');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 开卡、续费统计
     * @param $data
     * @return array
     */
    static public function recordStats($data)
    {
        $sql = DB::table('card_records as a')
            ->select(['a.user_id', 'b.name as user_name', 'a.type', DB::raw('count(1) as count')])
            ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
            ->groupBy('a.user_id', 'a.type');

        if (isset($data['start_time'])) $sql = $sql->where('a.created_at', '>=', $data['start_time'] . ' 00:00:00');
        if (isset($data['end_time'])) $sql = $sql->where('a.created_at', '<=', $data['end_time']. ' 23:59:59');

        $list = $sql->get();
        $detail = [];

        foreach ($list as $item) {
            $user_id = $item->user_id;

            if (!isset($detail[$user_id])) {
                $detail[$user_id] = [
                    'user_id' => $user_id,
                    'user_name' => $item->user_name,
                    'new_count' => 0,
                    'renew_count' => 0
                ];
            }

            if ($item->type == self::TYPE_NEW) $detail[$user_id]['new_count'] = $item->count;
            else $detail[$user_id]['renew_count'] = $item->count;
        }

        return $detail;
    }
}
