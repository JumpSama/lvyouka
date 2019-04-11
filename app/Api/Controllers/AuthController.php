<?php
/**
 * Author: JumpSama
 * Date: 2019/4/10
 * Time: 17:06
 */

namespace App\Api\Controllers;


use App\Card;
use App\Commodity;
use App\Member;
use App\Order;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['authenticateAdmin']]);
    }

    /**
     * 后台用户登录，获取token
     * @param Request $request
     * @return mixed
     */
    public function authenticateAdmin(Request $request)
    {
        $credentials = $request->only('account', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return $this->responseError([],'用户名或密码错误');
            }
        } catch (JWTException $e) {
            return $this->responseError([],'服务器内部错误');
        }

        $user = User::where('account', $credentials['account'])->first();

        if ($user->status == 0) return $this->responseError([],'用户已禁用');

        return $this->responseData(['token' => $token]);
    }

    /**
     * 登出
     * @return mixed
     */
    public function logout()
    {
        JWTAuth::invalidate();

        return $this->responseData();
    }

    /**
     * 获取登录用户
     * @return mixed
     */
    public function getUser()
    {
        $user = JWTAuth::user();

        $result = User::getUser($user['id']);

        return $this->responseData($result);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return mixed
     */
    public function password(Request $request)
    {
        if (!$request->has(['oldpassword', 'password', 'repassword'])) return $this->responseError([], '参数错误');

        $oldPassword = $request->input('oldpassword');
        $password = $request->input('password');
        $rePassword = $request->input('repassword');

        $user = JWTAuth::user();

        if (User::password($oldPassword, $password, $rePassword, $user['id'])) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 主页各项数据
     * @return mixed
     */
    public function homeCount()
    {
        $cardCount = Card::count();
        $memberCount = Member::count();
        $commodityCount = Commodity::count();
        $orderCount = Order::count();

        return $this->responseData([
            'card' => $cardCount,
            'member' => $memberCount,
            'commodity' => $commodityCount,
            'order' => $orderCount
        ]);
    }
}