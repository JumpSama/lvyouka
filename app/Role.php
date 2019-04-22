<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * 角色列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function roleList($data, $offset = 0, $limit = 10)
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
     * 全部角色
     * @return mixed
     */
    static public function roleAll()
    {
        return self::pluck('name', 'id');
    }

    /**
     * 角色保存
     * @param $data
     * @param $userId
     * @return bool
     */
    static public function roleStore($data, $userId)
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
            $sql->updated_by = $userId;
            $sql->save();

            // 先删除
            if (isset($data['id'])) RoleDetail::where('role_id', $data['id'])->delete();

            $now = Carbon::now();
            $ids = $data['ids'];
            $insertData = [];
            foreach ($ids as $id) {
                $insertData[] = [
                    'menu_id' => $id,
                    'role_id' => $sql->id,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            DB::table('role_details')->insert($insertData);

            // 日志
            if (isset($data['id'])) Log::add($userId, '修改角色-' . $data['name']);
            else Log::add($userId, '创建角色-' . $data['name']);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 角色详情
     * @param $id
     * @return mixed
     */
    static public function roleDetail($id)
    {
        $detail = self::find($id)->toArray();

        $ids = RoleDetail::where('role_id', $id)->pluck('menu_id');

        $detail['ids'] = $ids;

        return $detail;
    }

    /**
     * 角色删除
     * @param $id
     * @param $userId
     * @return bool
     */
    static public function roleDel($id, $userId)
    {
        DB::beginTransaction();

        try {
            $detail = self::find($id);
            $name = $detail->name;
            self::destroy($id);
            RoleDetail::where('role_id', $id)->delete();
            User::where('role', $id)->update(['role' => 0]);

            // 日志
            Log::add($userId, '删除角色-' . $name);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }
}
