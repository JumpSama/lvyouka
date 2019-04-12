<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    /**
     * 获取配置
     * @return mixed
     */
    static public function getConfig()
    {
        return self::select(['key', 'value', 'name', 'datatype'])->get();
    }

    /**
     * 更新配置
     * @param $key
     * @param $value
     * @param $name
     * @return bool
     */
    static public function setConfig($key, $value, $name)
    {
        $sql = self::where('key', $key)->first();

        if (!$sql) {
            $sql = new self;

            $sql->key = $key;
            $sql->name = $name;
        } else {
            if ($sql->value == $value) return true;
        }

        $sql->value = $value;

        return $sql->save();
    }
}
