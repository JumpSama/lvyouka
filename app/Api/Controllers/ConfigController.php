<?php
/**
 * Author: JumpSama
 * Date: 2019/4/12
 * Time: 9:01
 */

namespace App\Api\Controllers;


use App\Config;
use Illuminate\Http\Request;

class ConfigController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 获取配置
     * @return mixed
     */
    public function getConfig()
    {
        $value = Config::getConfig();

        return $this->responseData($value);
    }

    /**
     * 更新配置
     * @param Request $request
     * @return mixed
     */
    public function setConfig(Request $request)
    {
        $configs = $request->input('configs');

        $arr = json_decode($configs, true);

        foreach ($arr as $item) {
            if (!Config::setConfig($item['key'], $item['value'], $item['name'])) {
                return $this->responseError();
            }
        }

        return $this->responseData();
    }
}