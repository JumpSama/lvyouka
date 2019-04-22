<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Site extends Model
{
    /**
     * 场所列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function siteList($data, $offset = 0, $limit = 10)
    {
        $sql = new self;

        if (isset($data['name'])) $sql = $sql->where('name', 'like', '%' . $data['name'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 全部场所
     * @return mixed
     */
    static public function siteAll()
    {
        return self::pluck('name', 'id');
    }

    /**
     * 场所保存
     * @param $data
     * @param $userId
     * @return bool
     */
    static public function siteStore($data, $userId)
    {
        DB::beginTransaction();

        try {
            if (isset($data['id'])) {
                $sql = self::find($data['id']);
            } else {
                $sql = new self;
            }

            $sql->name = $data['name'];
            $sql->save();

            // 先删除
            if (isset($data['id'])) SiteItem::where('site_id', $data['id'])->delete();

            $now = Carbon::now();
            $items = json_decode($data['items'], true);
            $insertData = [];
            foreach ($items as $item) {
                $insertData[] = [
                    'site_id' => $sql->id,
                    'item_name' => $item['item_name'],
                    'item_count' => $item['item_count'],
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            DB::table('site_items')->insert($insertData);

            // 日志
            if (isset($data['id'])) Log::add($userId, '修改场所-' . $data['name']);
            else Log::add($userId, '添加场所-' . $data['name']);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 场所详情
     * @param $id
     * @return mixed
     */
    static public function siteDetail($id)
    {
        $detail = self::find($id)->toArray();

        $items = SiteItem::select(['id', 'item_name', 'item_count'])->where('site_id', $id)->get()->toArray();

        $detail['items'] = $items;

        return $detail;
    }

    /**
     * 场所删除
     * @param $id
     * @param $userId
     * @return bool
     */
    static public function siteDel($id, $userId)
    {
        DB::beginTransaction();

        try {
            $detail = self::find($id);
            $name = $detail->name;
            self::destroy($id);
            SiteItem::where('site_id', $id)->delete();
            User::where('site', $id)->update(['site' => 0]);

            // 日志
            Log::add($userId, '删除场所-' . $name);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }
}
