<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Withdraw extends Model
{
    const STATUS_WAIT = 0;  // 等待审核
    const STATUS_SUCCESS = 1;   // 已提现
    const STATUS_REFUSE = 2;    // 已拒绝

    const MAIN_USER = 1;    // 分销商用户
    const MAIN_MEMBER = 2;  // 分销商会员

    /**
     * 提现记录添加
     * @param $mainType
     * @param $mainId
     * @param $amount
     * @return bool
     */
    static public function add($mainType, $mainId, $amount)
    {
        $sql = new self;

        $sql->main_type = $mainType;
        $sql->main_id = $mainId;
        $sql->amount = $amount;
        $sql->status = self::STATUS_WAIT;

        return $sql->save();
    }

    /**
     * 提现审核操作
     * @param $id
     * @param $type
     * @param $userId
     * @return mixed
     */
    static public function operate($id, $type, $userId)
    {
        $sql = self::find($id);

        if ($type == 'approve') $sql->status = self::STATUS_SUCCESS;
        else if ($type == 'refuse') $sql->status = self::STATUS_REFUSE;
        else if ($type == 're_approve') $sql->status = self::STATUS_WAIT;

        // 日志
        if ($type != 're_approve') Log::add($userId, '审核' . ($type == 'approve' ? '通过' : '拒绝') . '提现申请(ID:' . $id . ')');

        return $sql->save();
    }

    /**
     * 分销用户提现申请列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @param int $mainId
     * @return array
     */
    static public function userList($data, $offset = 0, $limit = 10, $mainId = 0)
    {
        $sql = DB::table('withdraws as a')
            ->select(['a.id', 'a.status', 'a.main_type', 'a.main_id', 'a.amount', 'a.created_at', 'b.name'])
            ->leftJoin('users as b', 'a.main_id', '=', 'b.id')
            ->where('a.main_type', self::MAIN_USER);

        if ($mainId > 0) $sql = $sql->where('a.main_id', $mainId);
        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['start_time'])) $sql = $sql->where('a.created_at', '>=', $data['start_time'] . ' 00:00:00');
        if (isset($data['end_time'])) $sql = $sql->where('a.created_at', '<=', $data['end_time'] . ' 23:59:59');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }
}
