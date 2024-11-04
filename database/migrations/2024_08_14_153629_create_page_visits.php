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
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('uri');
            $table->string('name')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('visited_at')->useCurrent();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type',50)->default('unknown');
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();

            $table->index('uri');
            $table->index('visited_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
