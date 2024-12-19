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
        Schema::create('purchase_history', function (Blueprint $table) {
            $table->id(); // 自增主键
            $table->foreignId('account_id')->comment('关联的账户ID')->constrained('account')->onDelete('cascade');
            $table->string('purchase_id')->comment('购买ID'); // 购买ID
            $table->string('web_order_id')->comment('Web订单ID'); // 购买ID
            $table->string('dsid')->comment('DSID'); // DSID
            $table->decimal('invoice_amount', 15, 2)->nullable()->comment('发票金额'); // 发票金额
            $table->dateTime('invoice_date')->nullable()->comment('发票日期'); // 发票日期
            $table->dateTime('purchase_date')->comment('购买日期'); // 购买日期
            $table->boolean('is_pending_purchase')->comment('是否为待处理购买'); // 是否为待处理购买
            $table->decimal('estimated_total_amount', 15, 2)->nullable()->comment('预计总金额'); // 预计总金额
            $table->timestamps(); // 创建时间和更新时间
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_history');
    }
};
