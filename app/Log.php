<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
