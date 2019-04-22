<?php
/**
 * Author: JumpSama
 * Date: 2019/4/15
 * Time: 16:02
 */

namespace App\Http\Controllers;



use App\Commodity;
use App\Member;
use App\Order;
use App\PointFlow;
use App\SmsRecord;
use App\TempMember;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WechatController extends BaseController
{
    /**
     * 微信SDK
     * @var \Illuminate\Foundation\Application|mixed
     */
    public $wechat;

    public function __construct()
    {
        $this->middleware('wechat.oauth');
        $this->middleware('new.user', ['only' => ['detail', 'login', 'mall', 'me', 'order', 'point', 'sign']]);
        $this->wechat = app('wechat.official_account');
    }

    /**
     * 获取微信用户
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */
    public function getUser()
    {
        return session('wechat.oauth_user.default');
    }

    /**
     * 获取openid
     * @return mixed
     */
    public function getOpenid()
    {
        $user = session('wechat.oauth_user.default');

        return $user['original']['openid'];
    }

    /**
     * 获取用户
     * @return mixed
     */
    public function getMember()
    {
        return Member::where('openid', $this->getOpenid())->first();
    }

    /**
     * 图片上传
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        if($request->hasFile('image')) {
            $extension = $request->file('image')->extension();
            $image_extensions = config('app.image_extensions');

            if(strpos($image_extensions, $extension) === false) return $this->responseError('文件格式不正确');

            $dir = $request->input('dir', 'member');
            $path = 'uploads/' . $dir . '/' . $this->datePath();

            if ($fullPath = $request->file('image')->store($path)) {
                return $this->responseData([
                    'url' => config('app.image_domain') . $fullPath
                ]);
            } else {
                return $this->responseError();
            }
        } else {
            return $this->responseError();
        }
    }

    /**
     * 会员信息保存
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apply(Request $request)
    {
        $fields = ['name', 'sex', 'identity', 'phone', 'identity_front', 'identity_reverse', 'code'];

        if (!$request->filled($fields)) return $this->responseError('参数错误');

        $sms = SmsRecord::checkCode($request->input('phone'), $request->input('code'));

        if ($sms !== true)  return $this->responseError($sms);

        $data = $request->only($fields);

        $result = TempMember::add($data, $this->getOpenid());

        if ($result['flag'] == false) return $this->responseError($result['msg']);

        return $this->responseData(isset($result['params']) ? ['params' => $result['params']] : []);
    }

    /**
     * 在线续费
     * @return \Illuminate\Http\JsonResponse
     */
    public function renew()
    {
        $member = $this->getMember();
        $result = Member::renewByPay($member->id);

        if (is_string($result)) return $this->responseError($result);
        
        return $this->responseData(isset($result['params']) ? ['params' => $result['params']] : []);
    }

    /**
     * 未支付重新支付
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function repay(Request $request)
    {
        if (!$request->filled('id')) return $this->responseError('参数错误');

        $result = TempMember::repay($request->input('id'), $this->getOpenid());

        if ($result['flag'] == false) return $this->responseError($result['msg']);

        return $this->responseData(['params' => $result['params']]);
    }

    /**
     * 会员签到
     * @return \Illuminate\Http\JsonResponse
     */
    public function signIn()
    {
        if (Member::signIn($this->getOpenid())) return $this->responseData();

        return $this->responseError();
    }

    /**
     * 积分列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pointList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $data = $request->only(['type']);

        $member = $this->getMember();

        $list = PointFlow::pointList($data, $member->id, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 商品列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commodityList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $data = $request->only(['sort']);

        $list = Commodity::getList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 商品购买
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commodityBuy(Request $request)
    {
        if (!$request->filled('id')) return $this->responseError('参数错误');

        $member = $this->getMember();

        $result = Commodity::buy($request->input('id'), $member->id);

        if ($result === true) return $this->responseData();

        return $this->responseError($result);
    }

    /**
     * 会员订单列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $data = $request->only(['status']);

        $member = $this->getMember();

        $list = Order::getList($data, $member->id, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 获取二维码地址
     * @return \Illuminate\Http\JsonResponse
     */
    public function qrCode()
    {
        $member = $this->getMember();

        return $this->responseData(Member::getQrCode($member->id));
    }

    /**
     * 发送短信验证码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSms(Request $request)
    {
        if (!$request->filled('phone')) return $this->responseError('参数错误');

        $sms = SmsRecord::sendCode($request->input('phone'));

        if ($sms !== true) return $this->responseError();

        return $this->responseData();
    }

    /**
     * 商品详情页
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function detail(Request $request)
    {
        $id = $request->input('id', 0);

        if ($id > 0) {
            $detail = Commodity::find($id);
            $banner = json_decode($detail->banner, true);
        } else {
            return redirect('wechat/mall');
        }

        return view('detail', compact('detail', 'banner'));
    }

    /**
     * 会员注册页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function login()
    {
        $openid = $this->getOpenid();

        // 是否提交过
        $hasSubmit = false;
        $memberInfo = TempMember::get($openid);
        if ($memberInfo) $hasSubmit = true;
        $jsConfig = $this->wechat->jssdk->buildConfig(['chooseWXPay']);

        return view('login', compact('hasSubmit', 'memberInfo', 'jsConfig'));
    }

    /**
     * 积分商城页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function mall()
    {
        return view('mall');
    }

    /**
     * 会员首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function me()
    {
        $member = $this->getMember();
        $member['renew'] = Member::isRenew($member->overdue);

        $signDay = $this->getSignDay($member->sign_date, $member->sign_day);

        $jsConfig = $this->wechat->jssdk->buildConfig(['chooseWXPay']);

        return view('me', compact('member', 'signDay', 'jsConfig'));
    }

    /**
     * 会员订单列表页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function order()
    {
        return view('order');
    }

    /**
     * 会员积分记录页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function point()
    {
        $member = $this->getMember();


        $point = (integer)$member->point;

        return view('point', compact('point'));
    }

    /**
     * 会员签到页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function sign()
    {
        $member = $this->getMember();

        $tempDay = $this->getSignDay($member->sign_date, $member->sign_day);

        $point = (integer)$member->point;
        $signDay = $tempDay > 999 ? '999+' : $tempDay;
        $isSign = $member->sign_date === Carbon::today()->toDateString();

        $signData = $this->getSignDays($tempDay);

        $signWidth = $signData['width'];
        $signDays = $signData['days'];

        return view('sign', compact('point', 'isSign', 'signDay', 'signWidth', 'signDays'));
    }

    /**
     * 获取连续签到天数
     * @param $signDate
     * @param $day
     * @return int
     */
    public function getSignDay($signDate, $day)
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::today()->subDay()->toDateString();

        if ($signDate == $today || $signDate == $yesterday) return $day;

        return 0;
    }

    /**
     * 获取签到数据
     * @param $day
     * @return array
     */
    public function getSignDays($day)
    {
        $widths = [0, 12, 28, 43, 58, 74, 89, 100];

        $days = [];

        $index = $day > 7 ? 7 : $day;

        $left = $index - 1;
        if ($left < 0) $left = 0;

        $right = 7 - $index;
        if ($right > 6) $right = 6;

        $today = Carbon::today();

        for ($i = $left; $i > 0; $i--) {
            $temp = $today->subDay($i);
            $days[] = $temp->month . '.' . $temp->day;
        }

        $days[] = '今日';

        for ($i = 1; $i <= $right; $i++) {
            $temp = $today->addDay();
            $days[] = $temp->month . '.' . $temp->day;
        }

        $width = $widths[$index];

        return compact('width', 'days');
    }
}