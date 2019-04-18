<?php
/**
 * Author: JumpSama
 * Date: 2019/4/16
 * Time: 11:13
 */

namespace App\Http\Controllers;


use Carbon\Carbon;

class BaseController extends Controller
{
    /**
     * ajax响应
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseData($data = [], $msg = '操作成功', $code = 200)
    {
        return response()->json([
            'data' => $data,
            'msg' => $msg,
            'code' => $code
        ]);
    }

    /**
     * 错误响应
     * @param string $msg
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseError($msg = '操作失败', $data = [], $code = 400)
    {
        return $this->responseData($data, $msg, $code);
    }

    /**
     * 日期路径
     * @return string
     */
    public function datePath()
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        if ($month < 10) $month = 0 . $month;
        return $year . '/' . $month;
    }
}