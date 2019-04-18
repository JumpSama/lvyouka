<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTempMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_members', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(0)->comment('状态:0未支付 1待审核 2已拒绝');
            $table->string('name', 64)->comment('用户名');
            $table->tinyInteger('sex')->default(0)->comment('性别:0未知 1男 2女');
            $table->string('phone', 64)->unique()->comment('手机号');
            $table->string('identity', 64)->unique()->comment('身份证');
            $table->string('identity_front')->comment('身份证正面');
            $table->string('identity_reverse')->comment('身份证反面');
            $table->string('openid', 64)->unique()->comment('openid');
            $table->string('out_trade_no')->nullable()->comment('订单号');
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
        Schema::dropIfExists('temp_members');
    }
}
