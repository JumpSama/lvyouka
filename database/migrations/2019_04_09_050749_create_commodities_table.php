<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommoditiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commodities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->comment('商品名称');
            $table->string('intro')->nullable()->comment('商品简介');
            $table->float('show_price', 18, 2)->default(0.00)->comment('商品原价');
            $table->float('price', 18, 2)->default(0.00)->comment('商品价格');
            $table->tinyInteger('status')->default(1)->comment('商品状态:0下架 1上架');
            $table->string('image')->comment('商品主图');
            $table->string('banner')->comment('商品轮播图');
            $table->text('content')->comment('商品内容');
            $table->integer('stock')->default(0)->comment('库存');
            $table->integer('sale_count')->default(0)->comment('销量');
            $table->integer('created_by')->default(0)->comment('创建人');
            $table->integer('updated_by')->default(0)->comment('更新人');
            $table->tinyInteger('deleted')->default(0)->comment('是否删除');
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
        Schema::dropIfExists('commodities');
    }
}
