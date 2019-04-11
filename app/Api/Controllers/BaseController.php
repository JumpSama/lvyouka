<?php
/**
 * Author: JumpSama
 * Date: 2019/3/11
 * Time: 14:03
 */

namespace App\Api\Controllers;


use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;

class BaseController extends Controller
{
    use Helpers;

    /**
     * 响应
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return mixed
     */
    public function responseData($data = [], $msg = '操作成功', $code = 200)
    {
        return $this->response->array([
            'msg' => $msg,
            'code' => $code,
            'data' => $data
        ]);
    }

    /**
     * 错误响应
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return mixed
     */
    public function responseError($data = [], $msg = '操作失败', $code = 400)
    {
        return $this->responseData($data, $msg, $code);
    }
}