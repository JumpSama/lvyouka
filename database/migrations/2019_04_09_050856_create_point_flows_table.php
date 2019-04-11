<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->comment('用户id');
            $table->tinyInteger('type')->comment('类型');
            $table->integer('ref_id')->default(0)->comment('主表id');
            $table->decimal('amount', 18, 2)->comment('数额');
            $table->integer('created_by')->default(0)->comment('创建人');
            $table->integer('updated_by')->default(0)->comment('更新人');
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
        Schema::dropIfExists('point_flows');
    }
}
