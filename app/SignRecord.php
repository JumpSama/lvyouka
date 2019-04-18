<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SignRecord extends Model
{
    /**
     * 签到记录
     * @param $memberId
     * @param $today
     * @param $point
     * @return bool
     */
    static public function add($memberId, $today, $point)
    {
        $sql = new self;

        $sql->member_id = $memberId;
        $sql->sign_date = $today;
        $sql->point = $point;

        return $sql->save();
    }
}
