<?php

namespace App;

use Carbon\Carbon;
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
            ->select(['a.id', 'a.card_id', 'a.name', 'a.sex', 'a.phone', 'a.identity', 'a.status', 'a.overdue', 'a.point', 'b.number'])
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
}
