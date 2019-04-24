<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CardOrder extends Model
{
    const STATUS_PAY_NO = 0;    //待支付
    const STATUS_PAY_YES = 1;   //已支付

    /**
     * 卡片年费订单
     * @param $identity
     * @param $out_trade_no
     * @return bool
     */
    static public function add($identity, $out_trade_no)
    {
        $sql = new self;

        $sql->status = self::STATUS_PAY_NO;
        $sql->identity = $identity;
        $sql->amount = Config::get('Card.Price', 100);
        $sql->out_trade_no = $out_trade_no;

        return $sql->save();
    }
}
