<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(0)->comment('状态:0未支付 1已支付');
            $table->string('identity', 64)->comment('身份证号');
            $table->decimal('amount', 18, 2)->default(0)->comment('金额');
            $table->string('prepay_id', 64)->nullable()->comment('预支付ID');
            $table->string('out_trade_no', 64)->comment('订单号');
            $table->string('transaction_id', 64)->nullable()->comment('微信订单号');
            $table->string('refund_number', 64)->nullable()->comment('退款单号');
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
        Schema::dropIfExists('card_orders');
    }
}
