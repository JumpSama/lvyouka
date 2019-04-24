<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->comment('会员id');
            $table->date('overdue')->comment('过期时间');
            $table->tinyInteger('type')->comment('类型1.开卡 2.续费');
            $table->tinyInteger('pay_type')->comment('支付类型1.线下 2.线上');
            $table->integer('user_id')->default(0)->comment('操作人id');
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
        Schema::dropIfExists('card_records');
    }
}
