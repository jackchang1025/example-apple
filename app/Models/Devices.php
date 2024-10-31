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
 * @method static \Database\Factories\DevicesFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Devices newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Devices newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Devices query()
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereCurrentDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereDeviceClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereDeviceDetailHttpMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereDeviceDetailUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereHasActiveSurfAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereHasApplePayCards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereInfoboxImageLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereInfoboxImageLocation2x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereInfoboxImageLocation3x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereListImageLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereListImageLocation2x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereListImageLocation3x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereOsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereQualifiedDeviceClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereRemovalPending($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereSupportsVerificationCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereUnsupported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereUpdatedAt($value)
 * @property string $imei imei
 * @property string $meid meid
 * @property string $serial_number 序列号
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereImei($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereMeid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devices whereSerialNumber($value)
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
        'imei', //
        'meid', //
        'serial_number', //
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
}
