<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    const STATUS_OVERDUE = 0;   //已过期
    const STATUS_NORMAL = 1;    //正常

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_by', 'updated_by', 'updated_at',
    ];

    /**
     * 会员列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function memberList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('members as a')
            ->select(['a.id', 'a.name', 'a.sex', 'a.phone', 'a.identity', 'a.status', 'a.overdue', 'a.point', 'b.number'])
            ->leftJoin('cards as b', 'a.card_id', '=', 'b.id');

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['name'])) $sql = $sql->where('a.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['phone'])) $sql = $sql->where('a.phone', 'like', '%' . $data['phone'] . '%');
        if (isset($data['number'])) $sql = $sql->where('b.number', 'like', '%' . $data['number'] . '%');
        if (isset($data['identity'])) $sql = $sql->where('a.identity', 'like', '%' . $data['identity'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 会员详情
     * @param $data
     * @return array
     */
    static public function memberDetail($data)
    {
        if (isset($data['id'])) {
            return self::find($data['id'])->toArray();
        } else if (isset($data['identity'])) {
            return self::where('identity', $data['identity'])->first()->toArray();
        }

        return [];
    }
}
