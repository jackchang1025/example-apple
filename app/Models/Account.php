<?php

namespace App\Models;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $account 账号
 * @property string $password 密码
 * @property string $bind_phone 绑定的手机号码
 * @property string $bind_phone_address 绑定的手机号码所在地址
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereBindPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereBindPhoneAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @method static \Database\Factories\AccountFactory factory($count = null, $state = [])
 * @property AccountStatus $status
 * @property AccountStatus $type
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereStatus($value)
 * @property-read string $status_description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountLogs> $logs
 * @property-read int|null $logs_count
 * @mixin \Eloquent
 */
class Account extends Model
{
    use HasFactory;

    protected $table = 'account';

    protected $casts = [
        'status' => AccountStatus::class,
        'type' => AccountType::class,
    ];

    public function getStatusDescriptionAttribute(): string
    {
        return $this->status->description();
    }

    protected $fillable = ['account', 'password', 'bind_phone', 'bind_phone_address', 'id', 'status', 'type'];

    public function logs(): HasMany
    {
        return $this->hasMany(AccountLogs::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Devices::class);
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getSessionId(): string
    {
        return md5(sprintf('%s_%s', $this->account, $this->password));
    }

}
