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
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('account')->cascadeOnDelete();
            $table->string('raw_number')->comment('原始号码');
            $table->string('country_code', 10)->comment('国家代码');
            $table->string('country_dial_code', 10)->comment('国家拨号代码');
            $table->string('full_number_with_prefix')->comment('带国家代码的完整号码');
            $table->string('full_number_without_prefix')->comment('不带国家代码的号码');
            $table->boolean('is_vetted')->default(false)->comment('是否已验证');
            $table->boolean('is_trusted')->default(false)->comment('是否为可信号码');
            $table->boolean('is_login_handle')->default(false)->comment('是否为登录句柄');
            $table->string('type')->default('primary')->comment('电话类型');
            $table->json('additional_data')->nullable()->comment('额外数据');
            $table->timestamps();

            // 添加索引
            $table->index('raw_number');
            $table->index(['account_id', 'type']);
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
