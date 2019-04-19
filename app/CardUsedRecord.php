<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CardUsedRecord extends Model
{
    /**
     * 用户使用记录
     * @param $cardId
     * @param $userId
     * @return array
     */
    static public function getLog($cardId, $userId)
    {
        $user = User::find($userId);
        $member = Member::where('card_id', $cardId)->first();

        if (!$member) return ['msg' => '会员不存在'];
        if ($member->status != Member::STATUS_NORMAL) return ['msg' => '卡片已过期'];

        // 当前周期id
        $recordId = self::getRecordId($member->id);

        $list = DB::table('site_items as a')
            ->select(['a.item_name', 'a.item_count', 'b.count'])
            ->leftJoin('card_used_records as b', 'a.id', '=', 'b.item_id')
            ->where('a.site_id', $user->site)
            ->where('b.member_id', $member->id)
            ->where('b.record_id', $recordId)
            ->get();

        return ['list' => $list];
    }

    /**
     * 刷卡记录添加
     * @param $cardId
     * @param $userId
     * @param $itemId
     * @return bool|string
     */
    static public function add($cardId, $userId, $itemId)
    {
        $user = User::find($userId);
        $member = Member::where('card_id', $cardId)->first();

        if (!$member) return '会员不存在';
        if ($member->status != Member::STATUS_NORMAL) return '卡片已过期';

        $key = 'Card_Use_' . $member->id . '_' . $itemId;
        if (Cache::has($key)) return '刷卡间隔太短';

        DB::beginTransaction();

        try {
            $recordId = self::getRecordId($member->id);

            $sql = self::where('member_id', $member->id)->where('record_id', $recordId)->where('item_id', $itemId)->first();

            if ($sql) {
                $item = SiteItem::find($itemId);

                if ($sql->count >= $item->item_count) throw new \Exception('已达最大使用次数');

                $sql->count += 1;
            } else {
                $sql = new self;

                $sql->member_id = $member->id;
                $sql->record_id = $recordId;
                $sql->site_id = $user->site;
                $sql->item_id = $itemId;
                $sql->count = 1;
            }

            // 记录表保存
            $sql->save();

            // 积分增加
            $point = Config::get('Card.Award');
            $member->point = bcadd($member->point, $point, 2);
            $member->save();

            // 记录积分
            PointFlow::add($member->id, PointFlow::TYPE_USE, $point, $itemId);

            // 缓存
            Cache::put($key, time(), config('app.card_use_interval', 120));

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
    }

    /**
     * 获取当前周期id
     * @param $memberId
     * @return mixed
     */
    static public function getRecordId($memberId)
    {
        $today = Carbon::today()->toDateString();
        $record = CardRecord::where('member_id', $memberId)->where('overdue', '>=', $today)->first();
        return $record->id;
    }
}
