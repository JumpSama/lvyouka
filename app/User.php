<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    const STATUS_NORMAL = 1;
    const STATUS_DISABLE = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account', 'name', 'password', 'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * 修改密码
     * @param $oldPassword
     * @param $password
     * @param $rePassword
     * @param $userId
     * @return bool
     */
    static public function password($oldPassword, $password, $rePassword, $userId)
    {
        $user = self::select('password')->where('id', $userId)->first();

        if (!Hash::check($oldPassword, $user->password)) return false;

        if ($password != $rePassword) return false;

        $user = self::find($userId);

        $user->password = bcrypt($password);

        $res = $user->save();

        if ($res) Log::add($userId, '修改用户(ID:'.$userId.')密码');

        return $res;
    }

    /**
     * 获取用户和权限
     * @param $id
     * @return mixed
     */
    static public function getUser($id)
    {
        $user = self::select(['id', 'name', 'account', 'role'])->find($id)->toArray();

        if ($id = 1 && $user['account'] == 'admin') {
            $access = Menu::pluck('value');
        } else {
            $access = DB::table('role_details as a')
                ->leftJoin('menus as b', 'a.menu_id', '=', 'b.id')
                ->where('a.role_id', $user['role'])->pluck('b.value');
        }

        $user['access'] = $access;

        return $user;
    }

    /**
     * 用户列表
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    static public function userList($data, $offset = 0, $limit = 10)
    {
        $sql = DB::table('users as a')
        ->select(['a.id', 'a.name', 'a.account', 'a.status', 'a.created_at', 'b.name as role_name'])
        ->leftJoin('roles as b', 'a.role', '=', 'b.id')
        ->where('a.id', '>', 1);

        if (isset($data['name'])) $sql = $sql->where('a.name', 'like', '%' . $data['name'] . '%');
        if (isset($data['account'])) $sql = $sql->where('a.account', 'like', '%' . $data['account'] . '%');

        $total = $sql->count();
        $list = $sql->orderBy('a.created_at', 'desc')->offset($offset)->limit($limit)->get();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 用户保存
     * @param $data
     * @param $userId
     * @return bool
     */
    static public function userStore($data, $userId)
    {
        DB::beginTransaction();

        try {
            if (isset($data['id'])) {
                $sql = self::find($data['id']);
            } else {
                $sql = new self;
                $sql->created_by = $userId;
                $sql->password = bcrypt('123456');
            }

            $sql->name = $data['name'];
            $sql->role = $data['role'];
            $sql->account = $data['account'];
            $sql->updated_by = $userId;
            $sql->save();

            // 日志
            if (isset($data['id'])) Log::add($userId, '修改用户-' . $data['name']);
            else Log::add($userId, '创建用户-' . $data['name']);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 用户删除
     * @param $id
     * @param $userId
     * @return bool
     */
    static public function userDel($id, $userId)
    {
        DB::beginTransaction();

        try {
            $detail = self::find($id);
            $name = $detail->name;
            self::destroy($id);

            // 日志
            Log::add($userId, '删除用户-' . $name);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 用户操作
     * @param $data
     * @param $userId
     * @return boolean
     */
    static public function userOperate($data, $userId)
    {
        DB::beginTransaction();
        try {
            $sql = self::find($data['id']);

            if ($data['type'] == 'enable') {
                $sql->status = self::STATUS_NORMAL;
                Log::add($userId, '启用用户-' . $sql->name);
            } else {
                $sql->status = self::STATUS_DISABLE;
                Log::add($userId, '禁用用户-' . $sql->name);
            }

            $sql->save();

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }
}
