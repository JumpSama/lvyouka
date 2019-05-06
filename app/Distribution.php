<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Distribution extends Model
{
    const MAIN_USER = 1;    // 分销商用户
    const MAIN_MEMBER = 2;  // 分销商会员

    /**
     * 获取分销二维码
     * @param $mainType
     * @param $mainId
     * @return array
     */
    static public function getQrCode($mainType, $mainId)
    {
        $key = $mainType . '@' . $mainId;

        $ref = base64_encode($key);

        $url = config('app.url') . '/wechat/login?ref=' . $ref;

        $path = 'qrcode/' . Member::datePath() . $key . '.png';

        if (!Storage::exists($path)) {
            $image = QrCode::format('png')->size(250)->margin(0)->encoding('UTF-8')->generate($url);
            Storage::put($path, $image);
        }

        return [ 'url' => config('app.image_domain') . $path ];
    }

    /**
     * 获取佣金
     * @param $mainType
     * @param $mainId
     * @return int|mixed
     */
    static public function getAmount($mainType, $mainId)
    {
        $sql = self::where('main_type', $mainType)->where('main_id', $mainId)->first();

        if (!$sql) {
            $sql = new self;

            $sql->main_type = $mainType;
            $sql->main_id = $mainId;
            $sql->amount = 0;

            $sql->save();
        }

        return $sql->amount;
    }

    /**
     * 更改佣金
     * @param $mainType
     * @param $mainId
     * @param $amount
     * @return bool
     */
    static public function setAmount($mainType, $mainId, $amount)
    {
        $sql = self::where('main_type', $mainType)->where('main_id', $mainId)->first();

        if (!$sql) {
            $sql = new self;

            $sql->main_type = $mainType;
            $sql->main_id = $mainId;
            $sql->amount = $amount;
        } else {
            $sql->amount = bcadd($sql->amount, $amount, 2);
        }

        return $sql->save();
    }

    /**
     * 提现
     * @param $mainType
     * @param $mainId
     * @return bool|string
     */
    static public function withdraw($mainType, $mainId)
    {
        $amount = self::getAmount($mainType, $mainId);

        if ($amount <= 0) return '可提现金额不足';

        DB::beginTransaction();

        try {
            self::setAmount($mainType, $mainId, bcmul($amount, -1, 2));

            DistributionFlow::add(DistributionFlow::TYPE_WITHDRAW, $mainType, $mainId, bcmul($amount, -1, 2));

            Withdraw::add($mainType, $mainId, $amount);

            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return '提现记录添加失败';
        }
    }
}
