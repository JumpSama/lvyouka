<?php
/**
 * Author: JumpSama
 * Date: 2019/4/29
 * Time: 9:34
 */

namespace App\Api\Controllers;


use App\CardRecord;
use App\CardUsedRecord;
use App\Exports\CardRecordsExport;
use App\PointFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StatsController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 开卡、续费详情
     * @param Request $request
     * @return mixed
     */
    public function recordList(Request $request)
    {
        $data = $request->only(['type', 'pay_type', 'user_keyword', 'member_keyword', 'start_time', 'end_time']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = CardRecord::recordList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 开卡、续费详情导出
     * @param Request $request
     * @return mixed
     */
    public function recordExport(Request $request)
    {
        $data = $request->only(['type', 'pay_type', 'user_keyword', 'member_keyword', 'start_time', 'end_time']);

        try {
            $export = new CardRecordsExport();
            $export = $export->setData($data);
            $fileName = ($data['pay_type'] == 1 ? '线下' : '线上') . '开卡续费记录_' . time() . '.xlsx';
            $excel = Excel::store($export, $fileName);
            if ($excel) {
                $filePath = config('app.image_domain') . $fileName;
                return $this->responseData([ 'url' => $filePath ]);
            }
            return $this->responseError([], '导出失败');
        } catch (\Exception $exception) {
            Log::debug($exception->getMessage());
            return $this->responseError([], '导出失败');
        }
    }

    /**
     * 开卡、续费统计
     * @param Request $request
     * @return mixed
     */
    public function recordStats(Request $request)
    {
        $data = $request->only(['start_time', 'end_time']);

        $detail = CardRecord::recordStats($data);

        return $this->responseData($detail);
    }

    /**
     * 刷卡统计
     * @param Request $request
     * @return mixed
     */
    public function usedStats(Request $request)
    {
        $data = $request->only(['type', 'start_time', 'end_time']);

        $detail = CardUsedRecord::usedStats($data);

        return $this->responseData($detail);
    }

    /**
     * 刷卡详情
     * @param Request $request
     * @return mixed
     */
    public function usedList(Request $request)
    {
        $data = $request->only(['site_name', 'item_name', 'member_keyword', 'start_time', 'end_time']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = PointFlow::usedList($data, $offset, $limit);

        return $this->responseData($list);
    }
}