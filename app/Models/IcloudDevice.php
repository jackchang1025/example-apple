<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apple iCloud 设备模型
 *
 * @property int $id 主键ID
 * @property int $account_id 关联的账户ID
 * @property string $serial_number 设备序列号
 * @property string $os_version 操作系统版本
 * @property string $model_large_photo_url_2x 设备大图 2x 分辨率图片 URL
 * @property string $model_large_photo_url_1x 设备大图 1x 分辨率图片 URL
 * @property string $name 设备名称
 * @property string $imei 设备 IMEI 号码
 * @property string $model 设备型号
 * @property string $udid 设备唯一标识符
 * @property string $model_small_photo_url_2x 设备小图 2x 分辨率图片 URL
 * @property string $model_small_photo_url_1x 设备小图 1x 分辨率图片 URL
 * @property string $model_display_name 设备显示名称
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property-read \App\Models\Account $account
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereImei($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereModelDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereModelLargePhotoUrl1x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereModelLargePhotoUrl2x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereModelSmallPhotoUrl1x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereModelSmallPhotoUrl2x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereOsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereUdid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IcloudDevice whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IcloudDevice extends Model
{
    /**
     * 可批量赋值的属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'serial_number',
        'os_version',
        'model_large_photo_url_2x',
        'model_large_photo_url_1x',
        'name',
        'imei',
        'model',
        'udid',
        'model_small_photo_url_2x',
        'model_small_photo_url_1x',
        'model_display_name',
    ];

    /**
     * 字段验证规则
     *
     * @var array<string, string>
     */
    public static array $rules = [
        'serial_number'            => 'required|string|unique:devices',
        'os_version'               => 'required|string',
        'model_large_photo_url_2x' => 'required|url',
        'model_large_photo_url_1x' => 'required|url',
        'name'                     => 'required|string',
        'imei'                     => 'required|string|unique:devices',
        'model'                    => 'required|string',
        'udid'                     => 'required|string|unique:devices',
        'model_small_photo_url_2x' => 'required|url',
        'model_small_photo_url_1x' => 'required|url',
        'model_display_name'       => 'required|string',
    ];


    /**
     * 获取与此设备关联的账户。
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
