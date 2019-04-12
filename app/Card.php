<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Card extends Model
{
    const STATUS_WAIT = 0;  //未激活
    const STATUS_NORMAL = 1;    //已激活
    const STATUS_LOST = 2;  //已挂失

    /**
     * 卡片列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function cardList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('cards as a')
        ->select(['a.id', 'a.number', 'a.status', 'a.member_id', 'b.name as member_name'])
        ->leftJoin('members as b', 'a.member_id', '=', 'b.id');

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['member_id'])) $sql = $sql->where('a.member_id', $data['member_id']);
        if (isset($data['number'])) $sql = $sql->where('a.number', 'like', '%' . $data['number'] . '%');
        if (isset($data['member_name'])) $sql = $sql->where('b.name', 'like', '%' . $data['member_name'] . '%');

        $total = $sql->count();
        $list = $sql->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }
}
