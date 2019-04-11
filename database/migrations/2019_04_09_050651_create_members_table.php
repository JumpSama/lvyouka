<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('用户名');
            $table->tinyInteger('sex')->default(0)->comment('性别:0未知 1男 2女');
            $table->string('phone')->comment('手机号');
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('identity')->unique()->comment('身份证');
            $table->string('identity_front')->nullable()->comment('身份证正面');
            $table->string('identity_reverse')->nullable()->comment('身份证反面');
            $table->string('openid')->nullable()->comment('openid');
            $table->tinyInteger('status')->default(0)->comment('会员状态:0已过期or未激活 1正常');
            $table->date('sign_date')->nullable()->comment('签到时间');
            $table->integer('sign_day')->nullable()->comment('签到天数');
            $table->date('overdue')->nullable()->comment('过期时间');
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
        Schema::dropIfExists('members');
    }
}
