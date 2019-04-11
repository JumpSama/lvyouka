<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Log extends Model
{
    /**
     * 操作日志添加
     * @param $userId
     * @param $content
     */
    static public function add($userId, $content)
    {
        $log = new self;

        $log->user_id = $userId;
        $log->content = $content;
        $log->ip = $_SERVER['REMOTE_ADDR'];

        $log->save();
    }

    /**
     * 日志列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function logList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('logs as a')
        ->select(['a.id', 'a.user_id', 'a.ip', 'a.content', 'a.created_at', 'b.name as user_name'])
        ->leftJoin('users as b', 'a.user_id', '=', 'b.id');

        if (isset($data['name'])) $sql = $sql->where('b.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['content'])) $sql = $sql->where('a.content', 'like', '%' . $data['content'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }
}
