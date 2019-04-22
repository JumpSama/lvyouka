<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CardUsedRecord extends Model
{
    /**
     * 用户使用记录
     * @param $data
     * @param $userId
     * @return array
     */
    static public function getLog($data, $userId)
    {
        $user = User::find($userId);

        // 刷卡根据卡号获取
        if (isset($data['card_number'])) {
            $card = Card::where('number', $data['card_number'])->first();

            if (!$card) return ['msg' => '卡片不存在'];
            if ($card->status == Card::STATUS_WAIT) return ['msg' => '卡片未激活'];
            if ($card->status == Card::STATUS_LOST) return ['msg' => '卡片已挂失'];
            if ($card->status == Card::STATUS_DISABLE) return ['msg' => '卡片已冻结'];

            $member = Member::where('card_id', $card->id)->first();
        } else {
            // 扫描二维码根据缓存获取
            $memberId = Redis::get($data['qrcode']);
            if (!$memberId)  return ['msg' => '二维码已过期'];
            $member = Member::find($memberId);
        }

        if (!$member) return ['msg' => '会员不存在'];
        if ($member->status != Member::STATUS_NORMAL) return ['msg' => '卡片已过期'];

        // 当前周期id
        $recordId = self::getRecordId($member->id);

        $items = SiteItem::select(['id', 'item_name', 'item_count'])->where('site_id', $user->site)->get();
        $counts = self::select(['item_id', 'count'])
            ->where('site_id', $user->site)->where('member_id', $member->id)->where('record_id', $recordId)->get();

        return ['detail' => $member, 'items' => $items, 'counts' => $counts];
    }

    /**
     * 刷卡记录添加
     * @param $memberId
     * @param $userId
     * @param $itemId
     * @return bool|string
     */
    static public function add($memberId, $userId, $itemId)
    {
        $user = User::find($userId);
        $member = Member::find($memberId);

        if (!$member) return '会员不存在';
        if ($member->status != Member::STATUS_NORMAL) return '卡片已过期';

        $key = 'Card_Use_' . $member->id . '_' . $itemId;
        if (Cache::has($key)) return '刷卡间隔太短';

        DB::beginTransaction();

        try {
            $recordId = self::getRecordId($memberId);

            $sql = self::where('member_id', $memberId)->where('record_id', $recordId)->where('item_id', $itemId)->first();

            if ($sql) {
                $item = SiteItem::find($itemId);

                if ($sql->count >= $item->item_count) throw new \Exception('已达最大使用次数');

                $sql->count += 1;
            } else {
                $sql = new self;

                $sql->member_id = $memberId;
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
        $record = CardRecord::where('member_id', $memberId)->where('overdue', '>=', $today)->orderBy('overdue', 'asc')->first();
        return $record->id;
    }
}
