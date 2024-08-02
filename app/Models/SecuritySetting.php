<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
