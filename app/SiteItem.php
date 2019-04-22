<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SiteItem extends Model
{
    /**
     * 更新
     * @param $id
     * @param $data
     * @return mixed
     */
    static public function updateById($id, $data)
    {
        return self::where('id',$id)->update($data);
    }
}
