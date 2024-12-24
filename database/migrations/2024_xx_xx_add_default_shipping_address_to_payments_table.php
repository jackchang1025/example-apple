<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->json('default_shipping_address')->nullable()->after('payment_method_option');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('default_shipping_address');
        });
    }
};
