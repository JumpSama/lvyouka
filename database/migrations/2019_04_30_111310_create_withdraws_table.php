<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWithdrawsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->comment('状态0.待审核 1.审核通过 2.审核驳回');
            $table->tinyInteger('main_type')->comment('分销商类型1.后台用户 2.前台会员');
            $table->tinyInteger('main_id')->comment('分销商ID');
            $table->decimal('amount', 18, 2)->comment('提现金额');
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
        Schema::dropIfExists('withdraws');
    }
}
