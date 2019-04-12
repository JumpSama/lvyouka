<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TempMember extends Model
{
    const STATUS_NOT = 0;      //未支付
    const STATUS_WAIT = 1;      //等待审核
    const STATUS_REFUSE = 2;    //已拒绝

    const DELETED_NO = 0;   //未删除
    const DELETED_YES = 1;  //已删除

    /**
     * 审核列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function approveList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('temp_members as a')
            ->select(['a.id', 'a.status', 'a.name', 'a.sex', 'a.phone', 'a.identity', 'a.identity_front', 'a.identity_reverse', 'b.id as member_id', 'b.name as member_name'])
            ->leftJoin('members as b', 'a.identity', '=', 'b.identity')
            ->where('a.deleted', self::DELETED_NO);

        if (isset($data['status'])) $sql = $sql->where('a.status', $data['status']);
        if (isset($data['name'])) $sql = $sql->where('a.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['phone'])) $sql = $sql->where('a.phone', 'like', '%' . $data['phone'] . '%');
        if (isset($data['identity'])) $sql = $sql->where('a.identity', 'like', '%' . $data['identity'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 审核操作
     * @param $id
     * @param $type
     * @param $userId
     * @return bool
     */
    static public function approveOperate($id, $type, $userId)
    {
        DB::beginTransaction();

        try {
            $detail = self::find($id);
            $member = Member::where('identity', $detail->identity)->first();

            if ($type == 'approve') {
                // 没有实体卡的用户
                if (!$member) {
                    $member = new Member();

                    $member->created_by = $userId;
                    $member->name = $detail->name;
                    $member->sex = $detail->sex;
                    $member->phone = $detail->phone;
                    $member->identity = $detail->identity;
                    $member->status = Member::STATUS_NORMAL;
                    $member->overdue = Carbon::now()->addDays(365)->toDateString();
                }

                // 同步公众号端数据
                $member->openid = $detail->openid;
                $member->identity_front = $detail->identity_front;
                $member->identity_reverse = $detail->identity_reverse;
                $member->updated_by = $userId;

                $member->save();

                // 删除临时表
                $detail->deleted = self::DELETED_YES;
                $detail->save();

                // 日志
                Log::add($userId, '审核通过用户-' . $detail->name . '(' . $detail->identity . '');
            } else {
                $detail->status = self::STATUS_REFUSE;

                // 退款
                if (!$member) {
                    // TODO 退款
                }

                $detail->save();

                // 日志
                Log::add($userId, '审核拒绝用户-' . $detail->name . '(' . $detail->identity . '');
            }

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }
}
