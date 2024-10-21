<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property array $configuration
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereConfiguration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereUpdatedAt($value)
 * @property int|null  $proxy_enabled {0:关闭代理, 1:开启代理}
 * @property int|null $ipaddress_enabled {0:不同步用户IP地址, 1:同步用户IP地址}
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereSynchronizeUserIpAddress($value)
 * @method static \Database\Factories\ProxyConfigurationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereIpaddressEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProxyConfiguration whereProxyEnabled($value)
 * @mixin \Eloquent
 */
class ProxyConfiguration extends Model
{
    use HasFactory;

    const int ON = 1;
    const int OFF = 0;

    protected $fillable = ['name', 'configuration', 'is_active','ipaddress_enabled','proxy_enabled'];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_active) {
                // 如果当前模型被设置为活动状态，将其他所有配置设置为非活动状态
                static::where('id', '!=', $model->id)->update(['is_active' => false]);
            }
        });
    }
}
