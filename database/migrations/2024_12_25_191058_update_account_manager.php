<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_managers', function (Blueprint $table) {

            // 基本信息
            $table->string('apple_id_display')->nullable()->comment('Apple ID显示名称');
            $table->json('name')->nullable()->comment('用户姓名信息(包含firstName, lastName等)');
            $table->string('localized_birthday')->nullable()->comment('本地化格式的生日信息');
            $table->json('primary_email_address')->nullable()->comment('主要邮箱地址信息');

            // 账户状态
            $table->boolean('is_paid_account')->default(false)->comment('是否为付费账户');
            $table->boolean('is_hsa_eligible')->default(false)->comment('是否有资格使用HSA(高级安全账户)');
            $table->boolean('is_hsa')->default(false)->comment('是否为HSA账户');

            // 安全特性
            $table->boolean('show_hsa2_recovery_key_section')->default(false)->comment('是否显示HSA2恢复密钥部分');
            $table->boolean('should_show_data_recovery_service_ui')->default(false)->comment(
                '是否显示数据恢复服务界面'
            );
            $table->boolean('should_show_recovery_key_ui')->default(false)->comment('是否显示恢复密钥界面');

            // 验证状态
            $table->boolean('exceeded_verification_attempts')->default(false)->comment('是否超过验证尝试次数');
            $table->boolean('scnt_required')->default(false)->comment('是否需要SCNT(安全码)验证');

            // 其他配置
            $table->json('alternate_email_addresses')->nullable()->comment('备用邮箱地址列表');
            $table->json('support_links')->nullable()->comment('支持链接列表');
            $table->json('modules')->nullable()->comment('模块配置信息');

            // 新增遗漏字段
            $table->boolean('enable_right_to_left_display')->default(false)->comment('是否启用从右到左显示');
            $table->boolean('login_handle_available')->default(true)->comment('登录句柄是否可用');
            $table->boolean('is_apple_id_and_primary_email_same')->default(false)->comment('Apple ID是否与主邮箱相同');
            $table->boolean('should_show_beneficiary_ui')->default(false)->comment('是否显示受益人UI');
            $table->boolean('show_npa')->default(false)->comment('是否显示NPA');
            $table->string('name_order')->nullable()->comment('姓名顺序');
            $table->boolean('pronounce_names_required')->default(false)->comment('是否需要名字发音');
            $table->boolean('is_account_name_editable')->default(false)->comment('账户名称是否可编辑');
            $table->boolean('middle_name_required')->default(false)->comment('是否需要中间名');
            $table->string('person_name_order')->nullable()->comment('人名顺序');
            $table->boolean('rescue_email_exists')->default(false)->comment('是否存在救援邮箱');
            $table->boolean('non_fteu_enabled')->default(false)->comment('是否启用非FTEU');
            $table->boolean('no_space_required_in_name')->default(false)->comment('名字是否不需要空格');
            $table->boolean('is_redesign_sign_in_enabled')->default(false)->comment('是否启用重新设计的登录');
            $table->string('environment')->nullable()->comment('环境');
            $table->boolean('should_show_custodian_ui')->default(false)->comment('是否显示监护人UI');
            $table->integer('hide_my_email_count')->default(0)->comment('隐藏邮箱数量');
            $table->integer('use_person_name_in_messaging_max_length')->default(0)->comment('消息中使用的人名最大长度');
            $table->json('countries_with_phone_number_removal_restriction')->nullable()->comment(
                '限制电话号码删除的国家列表'
            );
            $table->json('localized_resources')->nullable()->comment('本地化资源');
            $table->json('apple_id_email_merge')->nullable()->comment('Apple ID邮箱合并信息');

            // 新增复杂对象字段 (以JSON形式存储)
            $table->json('country_features')->nullable()->comment('国家/地区功能特性配置');
            $table->string('api_key')->nullable()->comment('API密钥');
            $table->json('page_features')->nullable()->comment('页面特性配置');
            $table->json('add_alternate_email')->nullable()->comment('添加备用邮箱配置');
            $table->json('display_name')->nullable()->comment('显示名称配置');
            $table->json('apple_id')->nullable()->comment('Apple ID详细信息');
            $table->json('primary_email_address_display')->nullable()->comment('主邮箱地址显示配置');
            $table->json('account')->nullable()->comment('账户详细信息');
            $table->json('edit_alternate_email')->nullable()->comment('编辑备用邮箱配置');

            // 其他遗漏的基础字段
            $table->boolean('show_data_recovery_service_ui')->default(false)->comment('是否显示数据恢复服务UI');
            $table->boolean('should_allow_add_alternate_email')->default(true)->comment('是否允许添加备用邮箱');
            $table->boolean('obfuscate_birthday')->default(false)->comment('是否混淆生日');

        });


    }

    public function down(): void
    {
        Schema::table('account_managers', function (Blueprint $table) {
            $table->dropColumn('apple_id_display');
            $table->dropColumn('name');
            $table->dropColumn('localized_birthday');
            $table->dropColumn('primary_email_address');
            $table->dropColumn('is_paid_account');
            $table->dropColumn('is_hsa_eligible');
            $table->dropColumn('is_hsa');
            $table->dropColumn('show_hsa2_recovery_key_section');
            $table->dropColumn('should_show_data_recovery_service_ui');
            $table->dropColumn('should_show_recovery_key_ui');
            $table->dropColumn('exceeded_verification_attempts');
            $table->dropColumn('scnt_required');
            $table->dropColumn('alternate_email_addresses');
            $table->dropColumn('support_links');
            $table->dropColumn('modules');

            // 新增字段
            $table->dropColumn([
                'enable_right_to_left_display',
                'login_handle_available',
                'is_apple_id_and_primary_email_same',
                'should_show_beneficiary_ui',
                'show_npa',
                'name_order',
                'pronounce_names_required',
                'is_account_name_editable',
                'middle_name_required',
                'person_name_order',
                'rescue_email_exists',
                'non_fteu_enabled',
                'no_space_required_in_name',
                'is_redesign_sign_in_enabled',
                'environment',
                'should_show_custodian_ui',
                'hide_my_email_count',
                'use_person_name_in_messaging_max_length',
                'countries_with_phone_number_removal_restriction',
                'localized_resources',
                'apple_id_email_merge',
            ]);

            // 新增字段的回滚
            $table->dropColumn([
                'country_features',
                'api_key',
                'page_features',
                'add_alternate_email',
                'display_name',
                'apple_id',
                'primary_email_address_display',
                'account',
                'edit_alternate_email',
                'show_data_recovery_service_ui',
                'should_allow_add_alternate_email',
                'obfuscate_birthday',
            ]);
        });
    }

};
