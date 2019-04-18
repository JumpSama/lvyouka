<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    const STATUS_WAIT = 0;  //未支付
    const STATUS_PAYED = 1; //已兑换
    const STATUS_DONE = 2;  //已完成

    /**
     * 订单列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function orderList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('orders as a')
            ->select(['a.id', 'a.member_id', 'a.commodity_id', 'a.amount', 'a.status', 'a.created_at', 'b.name as commodity_name', 'c.name as member_name', 'c.phone'])
            ->leftJoin('commodities as b', 'a.commodity_id', '=', 'b.id')
            ->leftJoin('members as c', 'a.member_id', '=', 'c.id');

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['name'])) $sql = $sql->where('b.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['keyword'])) {
            $keyword = $data['keyword'];
            $sql = $sql->where(function($q) use ($keyword) {
                $q->where('c.name', 'like', '%'.$keyword.'%')
                    ->orWhere('c.phone', 'like', '%'.$keyword.'%');
            });
        }

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 订单操作
     * @param $id
     * @param $type
     * @param $userId
     * @return bool
     */
    static public function orderOperate($id, $type, $userId)
    {
        $sql = self::find($id);

        if ($type == 'done') {
            $sql->status = self::STATUS_DONE;
            $sql->updated_by = $userId;
            return $sql->save();
        }

        return true;
    }

    /**
     * 订单添加
     * @param $memberId
     * @param $commodityId
     * @param $amount
     * @return bool
     */
    static public function add($memberId, $commodityId, $amount)
    {
        $sql = new self;

        $sql->member_id = $memberId;
        $sql->commodity_id = $commodityId;
        $sql->amount = $amount;
        $sql->status = self::STATUS_PAYED;

        return $sql->save();
    }

    /**
     * 会员订单列表
     * @param $data
     * @param $memberId
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function getList($data, $memberId, $offset = 0, $limit = 10)
    {
        $sql = DB::table('orders as a')
            ->select(['a.id', 'a.amount', 'a.status', 'a.created_at', 'b.name', 'b.image'])
            ->leftJoin('commodities as b', 'a.commodity_id', '=', 'b.id')
            ->where('a.member_id', $memberId);

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }
}
