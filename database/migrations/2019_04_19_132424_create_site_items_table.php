<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSiteItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id')->comment('场所id');
            $table->string('item_name')->comment('项目名称');
            $table->integer('item_count')->comment('项目次数');
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
        Schema::dropIfExists('site_items');
    }
}
