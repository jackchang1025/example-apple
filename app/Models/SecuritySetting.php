<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 
 *
 * @property int $id
 * @property array|null $authorized_ips
 * @property string|null $safe_entrance
 * @property array $configuration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting whereAuthorizedIps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting whereSafeEntrance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecuritySetting whereConfiguration($value)
 * @mixin \Eloquent
 */
class SecuritySetting extends Model
{
    use HasFactory;

    protected $fillable = ['authorized_ips', 'safe_entrance','configuration'];

    protected $casts = [
        'authorized_ips' => 'array',
        'configuration' => 'array',
    ];

    public static function booted(): void
    {
        static::updated(function () {
            Cache::delete('security_setting');
        });

        static::deleted(function () {
            Cache::delete('security_setting');
        });
    }
}
