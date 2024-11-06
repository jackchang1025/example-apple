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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('valid_from')->nullable()->comment('有效期开始时间');
            $table->timestamp('valid_until')->nullable()->comment('有效期结束时间');
            $table->boolean('is_active')->default(true)->comment('是否激活');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('valid_from');
            $table->dropColumn('valid_until');
            $table->dropColumn('is_active');
        });
    }
};
