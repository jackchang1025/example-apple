<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade')->comment('关联的家庭组ID');
            $table->string('last_name')->comment('姓氏');
            $table->string('dsid')->comment('Apple DSID');
            $table->string('original_invitation_email')->comment('初始邀请邮箱');
            $table->string('full_name')->comment('全名');
            $table->string('age_classification')->comment('年龄分类');
            $table->string('apple_id_for_purchases')->comment('用于购买的 Apple ID');
            $table->string('apple_id')->comment('Apple ID');
            $table->string('first_name')->comment('名字');
            $table->string('dsid_for_purchases')->comment('用于购买的 DSID');
            $table->boolean('has_parental_privileges')->default(false)->comment('是否有家长权限');
            $table->boolean('has_screen_time_enabled')->default(false)->comment('是否启用屏幕使用时间');
            $table->boolean('has_ask_to_buy_enabled')->default(false)->comment('是否启用购买请求');
            $table->boolean('has_share_purchases_enabled')->default(false)->comment('是否启用购买项目共享');
            $table->boolean('has_share_my_location_enabled')->default(false)->comment('是否启用位置共享');
            $table->json('share_my_location_enabled_family_members')->nullable()->comment('启用位置共享的家庭成员列表');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('family_members');
    }
};
