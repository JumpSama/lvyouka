<?php

namespace App;

use App\Http\Controllers\PayController;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
            ->select(['a.id', 'a.card_id', 'a.name', 'a.sex', 'a.phone', 'a.identity', 'a.status', 'a.overdue', 'a.point', 'b.number', 'b.status as card_status'])
            ->leftJoin('cards as b', 'a.card_id', '=', 'b.id');

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['name'])) $sql = $sql->where('a.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['phone'])) $sql = $sql->where('a.phone', 'like', '%' . $data['phone'] . '%');
        if (isset($data['number'])) $sql = $sql->where('b.number', 'like', '%' . $data['number'] . '%');
        if (isset($data['identity'])) $sql = $sql->where('a.identity', 'like', '%' . $data['identity'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

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

    /**
     * 检查信息是否一致
     * @param $data
     * @return mixed
     */
    static public function checkParams($data)
    {
        return self::where('identity', $data['identity'])
            ->where('name', $data['name'])
            ->where('sex', $data['sex'])
            ->where('phone', $data['phone'])
            ->where('name', $data['name'])
            ->whereNull('openid')->count() > 0;
    }

    /**
     * 会员签到
     * @param $openid
     * @return bool
     */
    static public function signIn($openid)
    {
        DB::beginTransaction();

        try {
            $member = self::where('openid', $openid)->first();

            $today = Carbon::today()->toDateString();

            if ($member->sign_date != $today) {
                $yesterday = Carbon::today()->subDay()->toDateString();

                $signDate = $member->sign_date;

                // 连续签到
                if ($yesterday == $signDate) {
                    $signDay = $member->sign_day + 1;
                    $member->sign_day = $signDay;
                } else {
                    $signDay = 1;
                    $member->sign_day = $signDay;
                }

                $point = self::getPoint($signDay);

                $member->point = bcadd($member->point, $point, 2);

                $member->sign_date = $today;

                $member->save();

                // 签到记录
                SignRecord::add($member->id, $today, $point);

                // 积分记录
                PointFlow::add($member->id, 1, $point);

                DB::commit();
                return true;
            } else {
                throw new \Exception('已签到');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 获取当天积分
     * @param $day
     * @return string
     */
    static public function getPoint($day)
    {
        if ($day > 7) $day = 7;

        return bcmul($day, 1, 0);
    }

    /**
     * 获取二维码地址
     * @param $id
     * @return array
     */
    static public function getQrCode($id)
    {
        $flag = 'qr_code_' . $id;
        $key = Redis::get($flag);

        if (empty($key)) {
            $key = str_random(20) . '_' . time();

            Redis::set($flag, $key);
            Redis::expire($flag, 900);

            Redis::set($key, $id);
            Redis::expire($key, 900);
        }

        $path = 'qrcode/' . self::datePath() . $key . '.png';

        if (!Storage::exists($path)) {
            $image = QrCode::format('png')->size(250)->margin(0)->encoding('UTF-8')->generate($key);
            Storage::put($path, $image);
        }

        return [
            'url' => config('app.image_domain') . $path
        ];
    }

    /**
     * 日期路径
     * @return string
     */
   static public function datePath()
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        if ($month < 10) $month = 0 . $month;
        return $year . '/' . $month . '/';
    }

    /**
     * 挂失绑定新卡
     * @param $data
     * @param $userId
     * @return bool|string
     */
    static public function change($data, $userId)
    {
        $card = Card::where('number', $data['card_number'])->first();

        if (!$card) return '卡片不存在';

        if ($card->status != Card::STATUS_WAIT) return '卡片已绑定其他会员';

        $member = self::where('identity', $data['identity'])->first();

        if (!$member) return '会员不存在';

        if (empty($member->card_id)) return '会员未绑定其他卡';

        DB::beginTransaction();

        try {
            // 卡片挂失
            Card::lost($member->card_id);

            $member->card_id = $card->id;
            $member->save();

            // 卡片激活
            Card::activate($card->id, $member->id);

            // 日志
            Log::add($userId, '会员挂失实体卡-' . $member->name . '(' . $data['identity'] . ')');

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return '绑定失败';
        }
    }

    /**
     * 绑定实体卡
     * @param $data
     * @param $userId
     * @return bool|string
     */
    static public function bind($data, $userId)
    {
        $card = Card::where('number', $data['card_number'])->first();

        if (!$card) return '卡片不存在';

        if ($card->status != Card::STATUS_WAIT) return '卡片已绑其他会员或已挂失';

        $member = self::where('identity', $data['identity'])->first();

        if (!$member) return '会员不存在';

        if (!empty($member->card_id)) return '会员已绑定其他卡';

        DB::beginTransaction();

        try {
            // 存储头像
            $path = 'identity/' . self::datePath() . $data['identity'] . '.png';
            $avatar = substr($data['avatar'], strpos($data['avatar'], ',') + 1);
            Storage::put($path, base64_decode($avatar));

            $member->card_id = $card->id;
            $member->avatar = config('app.image_domain') . $path;
            $member->save();

            // 卡片激活
            Card::activate($card->id, $member->id);

            // 日志
            Log::add($userId, '绑定实体卡-' . $member->name . '(' . $data['identity'] . ')');

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return '绑定失败';
        }
    }

    /**
     * 实体卡开卡
     * @param $data
     * @param $userId
     * @return bool|string
     */
    static public function add($data, $userId)
    {
        // 参数校验
        $unique = self::checkUnique($data['identity'], $data['phone'], $data['card_number']);
        if ($unique !== true) return $unique;

        // 短信验证
        $sms = SmsRecord::checkCode($data['phone'], $data['code']);
        if ($sms !== true) return $sms;

        DB::beginTransaction();

        try {
            $sql = new self;

            // 过期时间
            $overdue = Carbon::now()->addDays(365)->toDateString();

            $cardId = Card::getIdByNumber($data['card_number']);

            // 存储头像
            $path = 'identity/' . self::datePath() . $data['identity'] . '.png';
            $avatar = substr($data['avatar'], strpos($data['avatar'], ',') + 1);
            Storage::put($path, base64_decode($avatar));

            $sql->card_id = $cardId;
            $sql->name = $data['name'];
            $sql->sex = $data['sex'];
            $sql->phone = $data['phone'];
            $sql->avatar = config('app.image_domain') . $path;
            $sql->identity = $data['identity'];
            $sql->status = self::STATUS_NORMAL;
            $sql->overdue = $overdue;
            $sql->created_by = $userId;
            $sql->updated_by = $userId;

            $sql->save();

            // 开卡记录
            CardRecord::add($sql->id, $overdue, CardRecord::TYPE_NEW, CardRecord::PAY_OFFLINE, $userId);

            // 卡片激活
            Card::activate($cardId, $sql->id);

            // 日志
            Log::add($userId, '实体卡开卡-' . $data['name'] . '(' . $data['identity'] . ')');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug($e->getMessage());
            DB::rollBack();
            return '保存失败';
        }
    }

    /**
     * 参数校验
     * @param $identity
     * @param $phone
     * @param $cardNumber
     * @return bool|string
     */
    static public function checkUnique($identity, $phone, $cardNumber)
    {
        $identityCount = self::where('identity', $identity)->count();

        if ($identityCount > 0) return '身份证已存在';

        $phoneCount = self::where('phone', $phone)->count();

        if ($phoneCount > 0) return '手机号已存在';

        $card = Card::where('number', $cardNumber)->first();

        if (!$card)  return '卡号无效';

        if ($card->status != Card::STATUS_WAIT) return '卡片已绑定其他会员';

        return true;
    }

    /**
     * 刷卡获取会员详情
     * @param $cardNumber
     * @return array
     */
    static public function memberDetailByCard($cardNumber)
    {
        $card = Card::where('number', $cardNumber)->first();

        if (!$card) return ['msg' => '卡片不存在'];
        if ($card->status !== Card::STATUS_NORMAL)  return ['msg' => '卡片未激活或已冻结'];

        $detail = self::where('card_id', $card->id)->first()->toArray();

        if (!$detail) return ['msg' => '会员不存在'];

        $detail['renew'] = self::isRenew($detail['overdue']);

        return $detail;
    }

    /**
     * 是否能续费
     * @param $overdue
     * @return bool
     */
    static public function isRenew($overdue)
    {
        $today = Carbon::today()->toDateString();

        if ($today > $overdue) return true;

        $minDay = Config::get('Card.Renew', 30);

        if (Carbon::today()->addDays($minDay)->toDateString() >= $overdue) return true;

        return false;
    }

    /**
     * 实体卡续费
     * @param $cardNumber
     * @param $userId
     * @return bool|string
     */
    static public function renew($cardNumber, $userId)
    {
        DB::beginTransaction();

        try {
            $card = Card::where('number', $cardNumber)->first();

            if (!$card) throw new \Exception('卡片不存在');
            if ($card->status !== Card::STATUS_NORMAL) throw new \Exception('卡片未激活');

            $detail = self::where('card_id', $card->id)->first();

            if (!$detail) throw new \Exception('会员不存在');
            if (!self::isRenew($detail->overdue)) throw new \Exception('暂无法续费');

            if ($detail->overdue > Carbon::today()->toDateString()) {
                $overdue = (new Carbon($detail->overdue))->addDays(365)->toDateString();
            } else {
                $overdue = Carbon::today()->addDays(365)->toDateString();
            }

            $detail->overdue = $overdue;
            $detail->status = self::STATUS_NORMAL;
            $detail->save();

            // 续费记录
            CardRecord::add($detail->id, $overdue, CardRecord::TYPE_RENEW, CardRecord::PAY_OFFLINE, $userId);

            // 日志
            Log::add($userId, '实体卡续费-' . $detail->name . '(' . $detail->identity . ')');

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
    }

    /**
     * 在线续费
     * @param $id
     * @return array|bool|string
     */
    static public function renewByPay($id)
    {
        DB::beginTransaction();

        try {
            $member = self::find($id);

            if (!$member) throw new \Exception('会员不存在');
            if (!self::isRenew($member->overdue)) throw new \Exception('暂无法续费');

            $out_trade_no = str_random(20) . '-' . time();

            // 支付订单
            CardOrder::add($member->identity, $out_trade_no);

            $pay = new PayController();
            $payParams = $pay->cardPay($out_trade_no, $member->openid, 'renew');

            if ($payParams === false) throw new \Exception('调用支付失败');

            DB::commit();
            return ['params' => $payParams];
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
    }

    /**
     * 冻结实体卡
     * @param $id
     * @param $userId
     * @return bool|string
     */
    static public function disableCard($id, $userId)
    {
        $member = self::find($id);

        if (empty($member->card_id)) return '用户未绑定实体卡';

        $card = Card::find($member->card_id);

        if ($card->status == Card::STATUS_NORMAL) {
            $card->status = Card::STATUS_DISABLE;
            if ($card->save()) {
                Log::add($userId, '冻结实体卡-' . $member->name . '(' . $member->identity . ')');
                return true;
            }
        }

        return '冻结失败';
    }

    /**
     * 解冻实体卡
     * @param $id
     * @param $userId
     * @return bool|string
     */
    static public function enableCard($id, $userId)
    {
        $member = self::find($id);

        if (empty($member->card_id)) return '用户未绑定实体卡';

        $card = Card::find($member->card_id);

        if ($card->status == Card::STATUS_DISABLE) {
            $card->status = Card::STATUS_NORMAL;
            if ($card->save()) {
                Log::add($userId, '解冻实体卡-' . $member->name . '(' . $member->identity . ')');
                return true;
            }
        }

        return '解冻失败';
    }
}
