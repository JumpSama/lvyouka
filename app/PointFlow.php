<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PointFlow extends Model
{
    const TYPE_SIGN = 1;    // 签到
    const TYPE_BUY = 2;    // 购物
    const TYPE_USE = 3;    // 刷卡

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * 积分记录添加
     * @param $memberId
     * @param $type
     * @param $amount
     * @param int $refId
     * @return bool
     */
    static public function add($memberId, $type, $amount, $refId = 0)
    {
        $sql = new self;

        $sql->member_id = $memberId;
        $sql->type = $type;
        $sql->amount = $amount;
        $sql->ref_id = $refId;

        return $sql->save();
    }

    /**
     * 积分列表
     * @param $data
     * @param $memberId
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function pointList($data, $memberId, $offset = 0, $limit = 10)
    {
        $sql = self::where('member_id', $memberId);

        if (isset($data['type'])) {
            if ($data['type'] == 'in') $sql = $sql->where('amount', '>=', 0);
            else if ($data['type'] == 'out') $sql = $sql->where('amount', '<', 0);
        }

        $total = $sql->count();
        $list = $sql->orderBy('created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 刷卡详情
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function usedList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('point_flows as a')
            ->select(['a.created_at', 'a.member_id', 'b.name', 'b.phone', 'b.identity', 'c.item_name', 'd.name as site_name'])
            ->leftJoin('members as b', 'a.member_id', '=', 'b.id')
            ->leftJoin('site_items as c', 'a.ref_id', '=', 'c.id')
            ->leftJoin('sites as d', 'c.site_id', '=', 'd.id')
            ->where('a.type', self::TYPE_USE);

        if (isset($data['site_name'])) $sql = $sql->where('d.name', 'like', '%' . $data['site_name'] . '%');
        if (isset($data['item_name'])) $sql = $sql->where('c.item_name', 'like', '%' . $data['item_name'] . '%');
        if (isset($data['start_time'])) $sql = $sql->where('a.created_at', '>=', $data['start_time'] . ' 00:00:00');
        if (isset($data['end_time'])) $sql = $sql->where('a.created_at', '<=', $data['end_time']. ' 23:59:59');
        if (isset($data['member_keyword'])) {
            $memberKeyword = $data['member_keyword'];
            $sql = $sql->where(function($q) use ($memberKeyword) {
                $q->where('b.name', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('b.phone', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('b.identity', 'like', '%'.$memberKeyword.'%');
            });
        }

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }
}
