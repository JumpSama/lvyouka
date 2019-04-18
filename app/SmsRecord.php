<?php

namespace App;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SmsRecord extends Model
{
    const STATUS_DISABLED = 0;  // 已失效
    const STATUS_USED = 1;  // 已使用
    const STATUS_OVERDUE = 2;  // 已过期
    const STATUS_NORMAL = 10;  // 正常

    /**
     * 短信验证码校验
     * @param $phone
     * @param $code
     * @return bool|string
     */
    static public function checkCode($phone, $code)
    {
        $sms = self::where('phone', $phone)->orderBy('created_at', 'desc')->first();

        if (!$sms) {
            return '记录不存在';
        }

        if ($sms->status != self::STATUS_NORMAL) {
            return '验证码无效';
        }

        if ($sms->retry >= 3) {
            $sms->status = self::STATUS_DISABLED;
            $sms->save();
            return '重试次数过多';
        }

        $now = Carbon::now();
        $overdue = (new Carbon($sms->created_at))->addMinutes(config('app.sms_overdue', 30));

        if ($now > $overdue) {
            $sms->status = self::STATUS_OVERDUE;
            $sms->save();
            return '验证码已过期';
        }

        if ($sms->code != $code) {
            $sms->retry += 1;
            $sms->save();
            return '验证码错误';
        } else {
            $sms->status = self::STATUS_USED;
            $sms->save();
            return true;
        }
    }

    /**
     * 验证码发送
     * @param $phone
     * @return bool
     */
    static public function sendCode($phone)
    {
        if (Cache::get('sms_code_' . $phone)) return false;

        $code = (string)rand(100000, 999999);

        $sql = new self;

        $sql->status = self::STATUS_NORMAL;
        $sql->type = 1;
        $sql->phone = $phone;
        $sql->code = $code;

        $sql->save();

        if (!$sql->save()) return false;

        try {
            AlibabaCloud::accessKeyClient(config('app.sms_key'), config('app.sms_secret'))->regionId('cn-hangzhou')->asDefaultClient();

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => config('app.sms_sign'),
                        'TemplateCode' => config('app.sms_template'),
                        'TemplateParam' => json_encode(['code' => $code])
                    ],
                ])->request();

            if ($result->isSuccess()) {
                Cache::put('sms_code_' . $phone, time(), 1);
                return true;
            }
        } catch (ClientException $e) {
            $sql->remark = $e->getErrorMessage();
        } catch (ServerException $e) {
            $sql->remark = $e->getErrorMessage();
        }

        $sql->status = self::STATUS_DISABLED;
        $sql->save();

        return false;
    }

    /**
     * 添加
     * @param $phone
     * @param $code
     * @param $type
     * @return bool
     */
    static public function add($phone, $code, $type)
    {
        $sql = new self;

        $sql->status = self::STATUS_NORMAL;
        $sql->type = $type;
        $sql->phone = $phone;
        $sql->code = $code;

        return $sql->save();
    }
}
