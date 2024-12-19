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
        Schema::create('plis', function (Blueprint $table) {
            $table->id(); // 自增主键
            $table->foreignId('purchase_history_id')->comment('购买历史ID')->constrained('purchase_history')->onDelete(
                'cascade'
            );
            $table->string('purchase_id')->comment('购买ID');
            $table->string('item_id')->comment('项目ID'); // 项目ID
            $table->string('storefront_id')->nullable()->comment('商店前端ID'); // 商店前端ID
            $table->string('adam_id')->nullable()->comment('Adam ID'); // Adam ID
            $table->string('guid')->comment('GUID'); // GUID
            $table->string('amount_paid')->comment('支付金额'); // 支付金额
            $table->dateTime('pli_date')->comment('PLI日期'); // PLI日期
            $table->boolean('is_free_purchase')->default(false)->comment('是否为免费购买'); // 是否为免费购买
            $table->boolean('is_credit')->default(false)->comment('是否为信用'); // 是否为信用
            $table->string('line_item_type')->nullable()->comment('行项目类型'); // 行项目类型
            $table->string('title')->nullable()->comment('标题'); // 标题
            $table->json('localized_content')->nullable()->comment('本地化内容'); // 本地化内容
            $table->json('subscription_info')->nullable()->comment('订阅信息'); // 订阅信息
            $table->timestamps(); // 创建时间和更新时间
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plis');
    }
};
