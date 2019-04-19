<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
