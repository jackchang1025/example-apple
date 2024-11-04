<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->comment('关联的账户ID')->constrained('account')->onDelete('cascade');
            $table->string('device_id')->comment('设备唯一标识符');
            $table->string('name')->nullable()->comment('设备名称');
            $table->string('device_class')->nullable()->comment('设备类别,如iPhone');
            $table->string('qualified_device_class')->nullable()->comment('限定设备类别');
            $table->string('model_name')->nullable()->comment('设备型号名称,如iPhone XR');
            $table->string('os')->nullable()->comment('操作系统,如iOS');
            $table->string('os_version')->nullable()->comment('操作系统版本');
            $table->boolean('supports_verification_codes')->default(false)->comment('是否支持验证码');
            $table->boolean('current_device')->default(false)->comment('是否为当前设备');
            $table->boolean('unsupported')->default(false)->comment('是否不受支持');
            $table->boolean('has_apple_pay_cards')->default(false)->comment('是否有Apple Pay卡');
            $table->boolean('has_active_surf_account')->default(false)->comment('是否有活跃的Surf账户');
            $table->boolean('removal_pending')->default(false)->comment('是否待移除');
            $table->text('list_image_location')->nullable()->comment('列表图片地址');
            $table->text('list_image_location_2x')->nullable()->comment('2倍分辨率列表图片地址');
            $table->text('list_image_location_3x')->nullable()->comment('3倍分辨率列表图片地址');
            $table->text('infobox_image_location')->nullable()->comment('信息框图片地址');
            $table->text('infobox_image_location_2x')->nullable()->comment('2倍分辨率信息框图片地址');
            $table->text('infobox_image_location_3x')->nullable()->comment('3倍分辨率信息框图片地址');
            $table->text('device_detail_uri')->nullable()->comment('设备详情URI');
            $table->string('device_detail_http_method', 10)->nullable()->comment('获取设备详情的HTTP方法');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
