<?php
/**
 * Author: JumpSama
 * Date: 2019-04-09
 * Time: 22:20
 */

namespace App\Http\Controllers;


use App\CardOrder;
use App\CardRecord;
use App\Member;
use App\TempMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayController extends BaseController
{
    /**
     * 微信支付
     * @var \Illuminate\Foundation\Application|mixed
     */
    public $pay;

    /**
     * 回调地址
     * @var string
     */
    public $url;

    /**
     * 应用名称
     * @var \Illuminate\Config\Repository|mixed
     */
    public $name;

    /**
     * PayController constructor.
     */
    public function __construct()
    {
        $this->pay = app('wechat.payment');
        $this->url = config('app.url') . '/wechat/';
        $this->name = config('app.name_cn');
    }

    /**
     * 卡片开卡、续费
     * @param $out_trade_no
     * @param $openid
     * @param string $type
     * @return array|bool
     */
    public function cardPay($out_trade_no, $openid, $type = 'new')
    {
        $order = CardOrder::where('out_trade_no', $out_trade_no)->first();

        if ($order->amount > 0) {
            $result = $this->pay->order->unify([
                'body' => $this->name,
                'out_trade_no' => $out_trade_no,
                'total_fee' => $this->getFee($order->amount),
                'notify_url' => $this->url . ($type == 'new' ? 'card_callback' : 'renew_callback'),
                'trade_type' => 'JSAPI',
                'openid' => $openid
            ]);

            if ($result['return_code'] == 'SUCCESS') {
                $order->prepay_id = $result['prepay_id'];
                $order->save();

                return $this->getParams($out_trade_no, $order->amount, $result['prepay_id']);
            }

            return false;
        }

        return $this->getParams($out_trade_no, $order->amount);
    }

    /**
     * 退款
     * @param $out_trade_no
     * @return mixed
     */
    public function cardRefund($out_trade_no)
    {
        $order = CardOrder::where('out_trade_no', $out_trade_no)->first();

        $refundNumber = str_random(20) . '-' . time();

        $fee = $this->getFee($order->amount);

        $this->pay->refund->byOutTradeNumber($out_trade_no, $refundNumber, $fee, $fee);

        $order->refund_number = $refundNumber;

        return $order->save();
    }

    /**
     * 卡片开卡回调
     * @return mixed
     */
    public function cardCallback()
    {
        $response = $this->pay->handlePaidNotify(function ($message, $fail) {
            if ($message['return_code'] === 'SUCCESS') {
                DB::beginTransaction();

                try {
                    $order = CardOrder::where('out_trade_no', $message['out_trade_no'])->first();
                    $member = TempMember::where('out_trade_no', $message['out_trade_no'])->first();

                    // 更改订单状态
                    if ($order && $message['result_code'] == 'SUCCESS' && $order->status == CardOrder::STATUS_PAY_NO) {
                        $order->status = CardOrder::STATUS_PAY_YES;

                        if (isset($message['transaction_id'])) $order->transaction_id = $message['transaction_id'];

                        $order->save();

                        $member->status = TempMember::STATUS_WAIT;
                        $member->save();
                    }

                    DB::commit();
                    return true;
                } catch (\Exception $exception) {
                    Log::debug($exception->getMessage());
                    DB::rollBack();
                    return $fail('订单状态修改失败');
                }
            } else {
                return $fail('通信失败');
            }
        });

        return $response;
    }

    /**
     * 卡片续费回调
     * @return mixed
     */
    public function renewCallback()
    {
        $response = $this->pay->handlePaidNotify(function ($message, $fail) {
            if ($message['return_code'] === 'SUCCESS') {
                DB::beginTransaction();

                try {
                    $order = CardOrder::where('out_trade_no', $message['out_trade_no'])->first();

                    // 更改订单状态
                    if ($order && $message['result_code'] == 'SUCCESS' && $order->status == CardOrder::STATUS_PAY_NO) {
                        $order->status = CardOrder::STATUS_PAY_YES;

                        if (isset($message['transaction_id'])) $order->transaction_id = $message['transaction_id'];

                        $order->save();

                        // 更改会员到期时间
                        $member = Member::where('identity', $order->identity)->first();
                        if ($member->overdue > Carbon::today()->toDateString()) {
                            $overdue = (new Carbon($member->overdue))->addDays(365)->toDateString();
                        } else {
                            $overdue = Carbon::today()->addDays(365)->toDateString();
                        }
                        $member->overdue = $overdue;
                        $member->status = Member::STATUS_NORMAL;
                        $member->save();

                        // 开卡记录
                        CardRecord::add($member->id, $overdue, CardRecord::TYPE_RENEW, CardRecord::PAY_ONLINE);
                    }

                    DB::commit();
                    return true;
                } catch (\Exception $exception) {
                    Log::debug($exception->getMessage());
                    DB::rollBack();
                    return $fail('订单状态修改失败');
                }
            } else {
                return $fail('通信失败');
            }
        });

        return $response;
    }

    /**
     * 换算金额 元to分
     * @param $amount
     * @return string
     */
    public function getFee($amount)
    {
        return bcmul($amount, 100, 0);
    }

    /**
     * 组装支付参数
     * @param $out_trade_no
     * @param $price
     * @param string $prepay_id
     * @return array
     */
    public function getParams($out_trade_no, $price, $prepay_id = '')
    {
        $params = [];

        if (!empty($prepay_id)) {
            $params = $this->pay->jssdk->sdkConfig($prepay_id);
        }

        $params['out_trade_no'] = $out_trade_no;
        $params['price'] = $price;

        return $params;
    }
}