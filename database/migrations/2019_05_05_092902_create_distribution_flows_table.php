<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDistributionFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distribution_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('type')->comment('类型1.分销佣金 2.提现');
            $table->tinyInteger('main_type')->comment('分销商类型1.后台用户 2.前台会员');
            $table->integer('main_id')->comment('分销商ID');
            $table->integer('member_id')->default(0)->comment('注册会员ID');
            $table->decimal('amount', 18, 2)->comment('佣金');
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
        Schema::dropIfExists('distribution_flows');
    }
}
