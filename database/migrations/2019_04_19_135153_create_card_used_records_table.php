<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardUsedRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_used_records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->comment('会员id');
            $table->integer('record_id')->comment('周期id');
            $table->integer('site_id')->comment('场所id');
            $table->integer('item_id')->comment('项目id');
            $table->integer('count')->default(1)->comment('使用次数');
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
        Schema::dropIfExists('card_used_records');
    }
}
