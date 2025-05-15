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
        Schema::table('account', function (Blueprint $table) {
            // Add country_code column, making it nullable and placing it after 'bind_phone_address' for logical grouping
            $table->string('country_code')->nullable()->after('bind_phone_address');
            // Rename the 'account' column to 'appleid'
            $table->renameColumn('account', 'appleid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account', function (Blueprint $table) {
            // Rename 'appleid' back to 'account'
            $table->renameColumn('appleid', 'account');
            // Drop the 'country_code' column
            $table->dropColumn('country_code');
        });
    }
};
