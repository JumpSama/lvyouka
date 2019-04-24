<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CardRecord extends Model
{
    const TYPE_NEW = 1; // 开卡
    const TYPE_RENEW = 2;  // 续费

    const PAY_OFFLINE = 1;  // 线下付款
    const PAY_ONLINE = 2;    // 线上付款

    /**
     * 开卡、续费记录添加
     * @param $memberId
     * @param $overdue
     * @param $type
     * @param $payType
     * @param int $userId
     * @return bool
     */
    static public function add($memberId, $overdue, $type, $payType, $userId = 0)
    {
        $sql = new self;

        $sql->member_id = $memberId;
        $sql->overdue = $overdue;
        $sql->type = $type;
        $sql->pay_type = $payType;
        $sql->user_id = $userId;

        return $sql->save();
    }
}
