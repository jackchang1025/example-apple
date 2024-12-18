<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $account_id 关联的账号ID
 * @property array $config 配置
 * @property-read \App\Models\Account $account
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager query()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountManager whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AccountManager extends Model
{
    use HasFactory;

    protected $casts = [
        'config' => 'array',
    ];

    protected $fillable = [
        'account_id',
        'config',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
