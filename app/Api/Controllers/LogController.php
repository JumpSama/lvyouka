<?php
/**
 * Author: JumpSama
 * Date: 2019/4/11
 * Time: 16:24
 */

namespace App\Api\Controllers;


use App\Log;
use Illuminate\Http\Request;

class LogController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 日志列表
     * @param Request $request
     * @return mixed
     */
    public function logList(Request $request)
    {
        $data = $request->only(['name', 'content']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Log::logList($data, $offset, $limit);

        return $this->responseData($list);
    }
}