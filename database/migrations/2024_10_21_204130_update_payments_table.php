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
        Schema::table('payments', function (Blueprint $table) {
            // 修改现有列
            $table->renameColumn('name', 'payment_method_name');
            $table->renameColumn('display_name', 'payment_method_detail');

            // 添加新列
            $table->string('payment_id')->nullable()->after('id');
            $table->string('partner_login')->nullable();
            $table->json('phone_number')->nullable();
            $table->json('owner_name')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('payment_account_country_code')->nullable();
            $table->string('type');
            $table->boolean('is_primary')->default(false);
            $table->boolean('we_chat_pay')->default(false);
            $table->string('absolute_image_path')->nullable();
            $table->string('absolute_image_path_2x')->nullable();
            $table->boolean('payment_supported')->default(true);
            $table->boolean('family_card')->default(false);
            $table->boolean('expiration_supported')->default(false);
            $table->json('payment_method_option')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // 恢复原有列名
            $table->renameColumn('payment_method_name', 'name');
            $table->renameColumn('payment_method_detail', 'display_name');

            // 删除新添加的列
            $table->dropColumn([
                'payment_id',
                'partner_login',
                'phone_number',
                'owner_name',
                'billing_address',
                'payment_account_country_code',
                'type',
                'is_primary',
                'we_chat_pay',
                'absolute_image_path',
                'absolute_image_path_2x',
                'payment_supported',
                'family_card',
                'expiration_supported',
                'payment_method_option',
            ]);
        });
    }
};
