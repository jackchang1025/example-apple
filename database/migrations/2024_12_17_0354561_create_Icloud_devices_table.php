<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('icloud_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->comment('关联的账户ID')->constrained('account')->onDelete('cascade');
            $table->string('serial_number')->nullable()->comment('设备序列号');
            $table->string('os_version')->nullable()->comment('操作系统版本');
            $table->string('model_large_photo_url_2x')->nullable()->comment('设备大图 2x 分辨率图片 URL');
            $table->string('model_large_photo_url_1x')->nullable()->comment('设备大图 1x 分辨率图片 URL');
            $table->string('name')->nullable()->comment('设备名称');
            $table->string('imei')->nullable()->comment('设备 IMEI 号码');
            $table->string('model')->nullable()->comment('设备型号');
            $table->string('udid')->nullable()->comment('设备唯一标识符');
            $table->string('model_small_photo_url_2x')->nullable()->comment('设备小图 2x 分辨率图片 URL');
            $table->string('model_small_photo_url_1x')->nullable()->comment('设备小图 1x 分辨率图片 URL');
            $table->string('model_display_name')->nullable()->comment('设备显示名称');
            $table->timestamps();

            // 添加唯一索引
            $table->unique('serial_number');
            $table->unique('imei');
            $table->unique('udid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icloud_devices');
    }
};
