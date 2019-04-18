<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Commodity extends Model
{
    const DELETED_NO = 0;   //未删除
    const DELETED_YES = 1;  //已删除

    const STATUS_UP = 1;    //上架
    const STATUS_DOWN = 0;  //下架

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * 商品列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function commodityList($data, $offset = 0, $limit = 10)
    {
        $sql = self::where('deleted', self::DELETED_NO);

        if (isset($data['status'])) $sql = $sql->where('status', $data['status']);
        if (isset($data['name'])) $sql = $sql->where('name', 'like', '%' . $data['name'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 前台商品列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function getList($data, $offset = 0, $limit = 10)
    {
        $sql = self::select(['id', 'name', 'price', 'image', 'stock'])->where('status', self::STATUS_UP)->where('deleted', self::DELETED_NO);

        $sort = 'asc';
        if (isset($data['sort']) && $data['sort'] == 'down') $sort = 'desc';

        $total = $sql->count();
        $list = $sql->orderBy('price', $sort)->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 商品保存
     * @param $data
     * @param $userId
     * @return bool
     */
    static public function commodityStore($data, $userId)
    {
        DB::beginTransaction();

        try {
            if (isset($data['id'])) {
                $sql = self::find($data['id']);
            } else {
                $sql = new self;
                $sql->created_by = $userId;
            }

            $sql->name = $data['name'];
            $sql->price = $data['price'];
            $sql->status = $data['status'];
            $sql->image = $data['image'];
            $sql->banner = $data['banner'];
            $sql->content = $data['content'];
            $sql->stock = $data['stock'];
            $sql->updated_by = $userId;
            $sql->save();

            // 日志
            if (isset($data['id'])) Log::add($userId, '修改商品-' . $data['name']);
            else Log::add($userId, '添加商品-' . $data['name']);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            \Illuminate\Support\Facades\Log::debug($exception->getMessage());
            DB::rollBack();
            return false;
        }
    }

    /**
     * 商品详情
     * @param $id
     * @return mixed
     */
    static public function commodityDetail($id)
    {
        $detail = self::find($id)->toArray();

        return $detail;
    }

    /**
     * 商品删除
     * @param $id
     * @param $userId
     * @return bool
     */
    static public function commodityDel($id, $userId)
    {
        DB::beginTransaction();

        try {
            $sql = self::find($id);
            $sql->deleted = self::DELETED_YES;
            $sql->save();

            // 日志
            Log::add($userId, '删除商品-' . $sql->name);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 商品操作
     * @param $id
     * @param $type
     * @param $userId
     * @return bool
     */
    static public function commodityOperate($id, $type, $userId)
    {
        DB::beginTransaction();

        try {
            $sql = self::find($id);

            if ($type == 'up') $sql->status = self::STATUS_UP;
            else $sql->status = self::STATUS_DOWN;

            $sql->save();

            // 日志
            if ($type == 'up') Log::add($userId, '上架商品-' . $sql->name);
            else Log::add($userId, '下架商品-' . $sql->name);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    static public function buy($id, $memberId)
    {
        DB::beginTransaction();

        try {
            $commodity = self::find($id);

            if ($commodity->status != self::STATUS_UP || $commodity->deleted != self::DELETED_NO) throw new \Exception('商品已下架');

            if ($commodity->stock < 1) throw new \Exception('库存不足');

            $member = Member::find($memberId);

            if ($commodity->price > $member->point) throw new \Exception('积分不足');

            // 库存销量
            $commodity->stock -= 1;
            $commodity->sale_count += 1;
            $commodity->save();

            // 扣除积分
            $member->point = bcsub($member->point, $commodity->price, 2);
            $member->save();

            // 生成订单
            Order::add($memberId, $id, $commodity->price);

            // 积分记录
            PointFlow::add($memberId, PointFlow::TYPE_BUY, bcmul($commodity->price, -1, 2), $id);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
    }
}
