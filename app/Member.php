<?php

namespace App;

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
            ->select(['a.id', 'a.card_id', 'a.name', 'a.sex', 'a.phone', 'a.identity', 'a.status', 'a.overdue', 'a.point', 'b.number'])
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
     * 绑定实体卡
     * @param $data
     * @param $userId
     * @return bool|string
     */
    static public function bind($data, $userId)
    {
        $card = Card::where('id', $data['card_id'])->where('status', Card::STATUS_WAIT)->count();

        if ($card != 1) return '卡片已激活';

        $member = self::where('identity', $data['identity'])->where('name', $data['name'])->first();

        if (!$member) return '会员不存在';

        DB::beginTransaction();

        try {
            $member->card_id = $data['card_id'];
            $member->avatar = $data['avatar'];
            $member->save();

            // 卡片激活
            Card::activate($data['card_id'], $member->id);

            // 日志
            Log::add($userId, '绑定实体卡-' . $data['name'] . '(' . $data['identity'] . ')');

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
        $unique = self::checkUnique($data['identity'], $data['phone'], $data['card_id']);
        if ($unique !== false) return $unique;

        // 短信验证
        $sms = SmsRecord::checkCode($data['phone'], $data['code']);
        if ($sms !== false) return $sms;

        DB::beginTransaction();

        try {
            $sql = new self;

            // 过期时间
            $overdue = Carbon::now()->addDays(365)->toDateString();

            $sql->card_id = $data['card_id'];
            $sql->name = $data['name'];
            $sql->sex = $data['sex'];
            $sql->phone = $data['phone'];
            $sql->avatar = $data['avatar'];
            $sql->identity = $data['identity'];
            $sql->status = self::STATUS_NORMAL;
            $sql->overdue = $overdue;
            $sql->created_by = $userId;
            $sql->updated_by = $userId;

            $sql->save();

            // 开卡记录
            CardRecord::add($sql->id, $overdue);

            // 卡片激活
            Card::activate($data['card_id'], $sql->id);

            // 日志
            Log::add($userId, '实体卡开卡-' . $data['name'] . '(' . $data['identity'] . ')');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return '保存失败';
        }
    }

    /**
     * 参数校验
     * @param $identity
     * @param $phone
     * @param $cardId
     * @return bool|string
     */
    static public function checkUnique($identity, $phone, $cardId)
    {
        $identityCount = self::where('identity', $identity)->count();

        if ($identityCount > 0) return '身份证已存在';

        $phoneCount = self::where('phone', $phone)->count();

        if ($phoneCount > 0) return '手机号已存在';

        $cardCount = Card::where('id', $cardId)->where('status', Card::STATUS_WAIT)->count();

        if ($cardCount != 1) return '卡片已激活';

        return false;
    }
}
