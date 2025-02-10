<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apple ID账户管理器模型
 *
 * @property int $id
 * @property int $account_id 关联的账号ID
 *
 * // 基本信息
 * @property string $apple_id_display Apple ID显示名称
 * @property array $name 用户姓名信息(包含firstName, lastName等)
 * @property string $localized_birthday 本地化格式的生日信息
 * @property array $primary_email_address 主要邮箱地址信息
 *
 * // 账户状态
 * @property bool $is_paid_account 是否为付费账户
 * @property bool $is_hsa_eligible 是否有资格使用HSA
 * @property bool $is_hsa 是否为HSA账户
 *
 * // 安全特性
 * @property bool $show_hsa2_recovery_key_section 是否显示HSA2恢复密钥部分
 * @property bool $should_show_data_recovery_service_ui 是否显示数据恢复服务界面
 * @property bool $should_show_recovery_key_ui 是否显示恢复密钥界面
 *
 * // 验证状态
 * @property bool $exceeded_verification_attempts 是否超过验证尝试次数
 * @property bool $scnt_required 是否需要SCNT验证
 *
 * // UI显示控制
 * @property bool $enable_right_to_left_display 是否启用从右到左显示
 * @property bool $login_handle_available 登录句柄是否可用
 * @property bool $is_apple_id_and_primary_email_same Apple ID是否与主邮箱相同
 * @property bool $should_show_beneficiary_ui 是否显示受益人UI
 * @property bool $show_npa 是否显示NPA
 * @property bool $is_account_name_editable 账户名称是否可编辑
 * @property bool $should_show_custodian_ui 是否显示监护人UI
 * @property bool $show_data_recovery_service_ui 是否显示数据恢复服务UI
 *
 * // 名称相关
 * @property string $name_order 姓名顺序
 * @property bool $pronounce_names_required 是否需要名字发音
 * @property bool $middle_name_required 是否需要中间名
 * @property string $person_name_order 人名顺序
 * @property bool $no_space_required_in_name 名字是否不需要空格
 * @property int $use_person_name_in_messaging_max_length 消息中使用的人名最大长度
 *
 * // 邮箱相关
 * @property bool $rescue_email_exists 是否存在救援邮箱
 * @property array $alternate_email_addresses 备用邮箱地址列表
 * @property bool $should_allow_add_alternate_email 是否允许添加备用邮箱
 * @property int $hide_my_email_count 隐藏邮箱数量
 *
 * // 系统配置
 * @property bool $non_fteu_enabled 是否启用非FTEU
 * @property bool $is_redesign_sign_in_enabled 是否启用重新设计的登录
 * @property string $environment 环境
 * @property bool $obfuscate_birthday 是否混淆生日
 *
 * // JSON存储的复杂对象
 * @property array $country_features 国家/地区功能特性配置
 * @property string $api_key API密钥
 * @property array $page_features 页面特性配置
 * @property array $add_alternate_email 添加备用邮箱配置
 * @property array $display_name 显示名称配置
 * @property array $apple_id Apple ID详细信息
 * @property array $primary_email_address_display 主邮箱地址显示配置
 * @property array $account 账户详细信息
 * @property array $edit_alternate_email 编辑备用邮箱配置
 * @property array $support_links 支持链接列表
 * @property array $modules 模块配置信息
 * @property array $countries_with_phone_number_removal_restriction 限制电话号码删除的国家列表
 * @property array $localized_resources 本地化资源
 * @property array $apple_id_email_merge Apple ID邮箱合并信息
 * @property array $config 其他配置信息
 *
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AccountManager extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'apple_id_display',
        'name',
        'localized_birthday',
        'primary_email_address',
        'is_paid_account',
        'is_hsa_eligible',
        'is_hsa',
        'show_hsa2_recovery_key_section',
        'should_show_data_recovery_service_ui',
        'should_show_recovery_key_ui',
        'exceeded_verification_attempts',
        'scnt_required',
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
        'alternate_email_addresses',
        'support_links',
        'modules',
        'countries_with_phone_number_removal_restriction',
        'localized_resources',
        'apple_id_email_merge',
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
        'config',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 基本信息JSON
        'name'                                            => 'array',
        'primary_email_address'                           => 'array',

        // 布尔值字段
        'is_paid_account'                                 => 'boolean',
        'is_hsa_eligible'                                 => 'boolean',
        'is_hsa'                                          => 'boolean',
        'show_hsa2_recovery_key_section'                  => 'boolean',
        'should_show_data_recovery_service_ui'            => 'boolean',
        'should_show_recovery_key_ui'                     => 'boolean',
        'exceeded_verification_attempts'                  => 'boolean',
        'scnt_required'                                   => 'boolean',
        'enable_right_to_left_display'                    => 'boolean',
        'login_handle_available'                          => 'boolean',
        'is_apple_id_and_primary_email_same'              => 'boolean',
        'should_show_beneficiary_ui'                      => 'boolean',
        'show_npa'                                        => 'boolean',
        'pronounce_names_required'                        => 'boolean',
        'is_account_name_editable'                        => 'boolean',
        'middle_name_required'                            => 'boolean',
        'rescue_email_exists'                             => 'boolean',
        'non_fteu_enabled'                                => 'boolean',
        'no_space_required_in_name'                       => 'boolean',
        'is_redesign_sign_in_enabled'                     => 'boolean',
        'should_show_custodian_ui'                        => 'boolean',
        'show_data_recovery_service_ui'                   => 'boolean',
        'should_allow_add_alternate_email'                => 'boolean',
        'obfuscate_birthday'                              => 'boolean',

        // 整数字段
        'hide_my_email_count'                             => 'integer',
        'use_person_name_in_messaging_max_length'         => 'integer',

        // 复杂对象JSON
        'alternate_email_addresses'                       => 'array',
        'support_links'                                   => 'array',
        'modules'                                         => 'array',
        'countries_with_phone_number_removal_restriction' => 'array',
        'localized_resources'                             => 'array',
        'apple_id_email_merge'                            => 'array',
        'country_features'                                => 'array',
        'page_features'                                   => 'array',
        'add_alternate_email'                             => 'array',
        'display_name'                                    => 'array',
        'apple_id'                                        => 'array',
        'primary_email_address_display'                   => 'array',
        'account'                                         => 'array',
        'edit_alternate_email'                            => 'array',
        'config'                                          => 'array',
    ];

    /**
     * 获取关联的账户
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
