<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CardRecord extends Model
{
    /**
     * 开卡记录添加
     * @param $memberId
     * @param $overdue
     * @return bool
     */
    static public function add($memberId, $overdue)
    {
        $sql = new self;

        $sql->member_id = $memberId;
        $sql->overdue = $overdue;

        return $sql->save();
    }
}
