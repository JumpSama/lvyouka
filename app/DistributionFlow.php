<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DistributionFlow extends Model
{
    const TYPE_DISTRIBUTION = 1;    // 分销
    const TYPE_WITHDRAW = 2;    // 提现

    const MAIN_USER = 1;    // 分销商用户
    const MAIN_MEMBER = 2;  // 分销商会员

    /**
     * 分销佣金变动记录添加
     * @param $type
     * @param $mainType
     * @param $mainId
     * @param $amount
     * @return bool
     */
    static public function add($type, $mainType, $mainId, $amount)
    {
        $sql = new self;

        $sql->type = $type;
        $sql->main_type = $mainType;
        $sql->main_id = $mainId;
        $sql->amount = $amount;

        return $sql->save();
    }

    /**
     * 分销记录
     * @param $data
     * @param $mainType
     * @param $mainId
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function getList($data, $mainType, $mainId, $offset = 0, $limit = 10)
    {
        $sql = DB::table('distribution_flows as a')
            ->select(['a.id', 'a.type', 'a.main_type', 'a.main_id', 'a.member_id', 'a.amount', 'a.created_at', 'b.name'])
            ->leftJoin('members as b', 'a.member_id', '=', 'b.id')
            ->where('a.main_type', $mainType)
            ->where('a.main_id', $mainId);

        if (isset($data['type'])) $sql = $sql->where('a.type', $data['type']);
        if (isset($data['start_time'])) $sql= $sql->where('a.created_at', '>=', $data['start_time'] . ' 00:00:00');
        if (isset($data['end_time'])) $sql= $sql->where('a.created_at', '<=', $data['end_time'] . ' 23:59:59');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }
}
