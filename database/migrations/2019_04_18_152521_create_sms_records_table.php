<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_records', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->comment('状态:0.已失效 1.已使用 2.已过期 10.正常');
            $table->tinyInteger('retry')->default(0)->comment('重试次数');
            $table->tinyInteger('type')->comment('类型:1.注册验证码');
            $table->string('phone', 20)->comment('手机号');
            $table->string('code', 10)->comment('验证码');
            $table->string('remark', 64)->nullable()->comment('备注');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_records');
    }
}
