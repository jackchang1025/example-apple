<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('family_id')->unique()->comment('家庭组 ID');
            $table->string('organizer')->comment('组织者的 Apple ID');
            $table->string('etag')->comment('家庭组 etag 标识');
            $table->json('transfer_requests')->nullable()->comment('转移请求列表');
            $table->json('invitations')->nullable()->comment('邀请列表');
            $table->json('outgoing_transfer_requests')->nullable()->comment('发出的转移请求列表');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('families');
    }
};
