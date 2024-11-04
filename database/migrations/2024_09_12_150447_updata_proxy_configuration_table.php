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
        Schema::table('proxy_configurations', function (Blueprint $table) {
            $table->tinyInteger('ipaddress_enabled')->default(0)->nullable()->comment('{0:关闭代理, 1:开启代理}');
            $table->tinyInteger('proxy_enabled')->default(0)->nullable()->comment('{0:不同步用户IP地址, 1:同步用户IP地址}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxy_configurations', function (Blueprint $table) {
            $table->dropColumn('ipaddress_enabled');
            $table->dropColumn('proxy_enabled');
        });
    }
};
