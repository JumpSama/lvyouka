<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('configs')->insert([
            [
                'name' => '文旅惠民卡年费',
                'datatype' => 'string',
                'key' => 'Card.Price',
                'value' => '100',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'name' => '单次刷卡奖励积分',
                'datatype' => 'string',
                'key' => 'Card.Award',
                'value' => '100',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'name' => '签到规则说明',
                'datatype' => 'richtext',
                'key' => 'Sign.Explain',
                'value' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);
    }
}
