<?php
/**
 * Author: JumpSama
 * Date: 2019-03-16
 * Time: 19:46
 */

namespace App\Api\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;

class FileController extends BaseController
{
    /**
     * 图片上传
     * @param Request $request
     * @return mixed
     */
    public function imageUpload(Request $request)
    {
        if($request->hasFile('image')) {
            $extension = $request->file('image')->extension();
            $image_extensions = config('app.image_extensions');

            if(strpos($image_extensions, $extension) === false) return $this->responseError([], '文件格式不正确');

            $dir = $request->input('dir');
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