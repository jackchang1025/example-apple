<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('account')->comment('账号');
            $table->string('password')->comment('密码');
            $table->string('bind_phone')->comment('绑定的手机号码');
            $table->string('bind_phone_address')->comment('绑定的手机号码所在地址');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account');
    }
};
