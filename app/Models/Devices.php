<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Device
 *
 * @property int $id
 * @property int $account_id 关联的账户ID
 * @property string $device_id 设备唯一标识符
 * @property string|null $name 设备名称
 * @property string|null $device_class 设备类别,如iPhone
 * @property string|null $qualified_device_class 限定设备类别
 * @property string|null $model_name 设备型号名称,如iPhone XR
 * @property string|null $os 操作系统,如iOS
 * @property string|null $os_version 操作系统版本
 * @property bool $supports_verification_codes 是否支持验证码
 * @property bool $current_device 是否为当前设备
 * @property bool $unsupported 是否不受支持
 * @property bool $has_apple_pay_cards 是否有Apple Pay卡
 * @property bool $has_active_surf_account 是否有活跃的Surf账户
 * @property bool $removal_pending 是否待移除
 * @property string|null $list_image_location 列表图片地址
 * @property string|null $list_image_location_2x 2倍分辨率列表图片地址
 * @property string|null $list_image_location_3x 3倍分辨率列表图片地址
 * @property string|null $infobox_image_location 信息框图片地址
 * @property string|null $infobox_image_location_2x 2倍分辨率信息框图片地址
 * @property string|null $infobox_image_location_3x 3倍分辨率信息框图片地址
 * @property string|null $device_detail_uri 设备详情URI
 * @property string|null $device_detail_http_method 获取设备详情的HTTP方法
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @mixin \Eloquent
 */
class Devices extends Model
{
    use HasFactory;

    /**
     * 可以批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'account_id',           // 关联的账户ID
        'device_id',            // 设备唯一标识符
        'name',                 // 设备名称
        'device_class',         // 设备类别,如iPhone
        'qualified_device_class', // 限定设备类别
        'model_name',           // 设备型号名称,如iPhone XR
        'os',                   // 操作系统,如iOS
        'os_version',           // 操作系统版本
        'supports_verification_codes', // 是否支持验证码
        'current_device',       // 是否为当前设备
        'unsupported',          // 是否不受支持
        'has_apple_pay_cards',  // 是否有Apple Pay卡
        'has_active_surf_account', // 是否有活跃的Surf账户
        'removal_pending',      // 是否待移除
        'list_image_location',  // 列表图片地址
        'list_image_location_2x', // 2倍分辨率列表图片地址
        'list_image_location_3x', // 3倍分辨率列表图片地址
        'infobox_image_location', // 信息框图片地址
        'infobox_image_location_2x', // 2倍分辨率信息框图片地址
        'infobox_image_location_3x', // 3倍分辨率信息框图片地址
        'device_detail_uri',    // 设备详情URI
        'device_detail_http_method', // 获取设备详情的HTTP方法
    ];

    /**
     * 应该被转换成原生类型的属性。
     *
     * @var array
     */
    protected $casts = [
        'supports_verification_codes' => 'boolean',
        'current_device'              => 'boolean',
        'unsupported'                 => 'boolean',
        'has_apple_pay_cards'         => 'boolean',
        'has_active_surf_account'     => 'boolean',
        'removal_pending'             => 'boolean',
    ];

    /**
     * 获取与此设备关联的账户。
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function saveOrUpdate(): Model|Devices
    {
        $attributes = $this->getAttributes();

        // 使用 account_id 和 device_id 作为唯一标识
        $keys = ['account_id' => $this->account_id, 'device_id' => $this->device_id];

        // 移除 keys 中的字段，避免重复更新
        $values = array_diff_key($attributes, $keys);

        // 使用静态方法 updateOrCreate
        return self::updateOrCreate($keys, $values);
    }
}
