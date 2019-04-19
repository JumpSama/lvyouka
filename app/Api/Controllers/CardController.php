<?php
/**
 * Author: JumpSama
 * Date: 2019/4/12
 * Time: 9:53
 */

namespace App\Api\Controllers;


use App\Card;
use Illuminate\Http\Request;

class CardController extends BaseController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    /**
     * 卡片列表
     * @param Request $request
     * @return mixed
     */
    public function cardList(Request $request)
    {
        $data = $request->only(['number', 'status', 'member_id', 'member_name']);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Card::cardList($data, $offset, $limit);

        return $this->responseData($list);
    }

    /**
     * 卡片录入
     * @param Request $request
     * @return mixed
     */
    public function cardAdd(Request $request)
    {
        if (!$request->filled('number')) return $this->responseError([], '参数错误');

        $data = $request->only(['number']);

        if (Card::add($data)) return $this->responseData();

        return $this->responseError();
    }
}