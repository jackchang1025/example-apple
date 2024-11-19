<?php

namespace App\Models;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Devices> $devices
 * @property-read int|null $devices_count
 * @property-read \App\Models\Payment|null $payment
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereType($value)
 * @property-read \App\Models\FamilyMember|null $familyMember
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FamilyMember> $familyMembers
 * @property-read int|null $family_members_count
 * @property-read \App\Models\Family|null $family
 * @property string|null $dsid dsid
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereDsid($value)
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

    protected $fillable = ['account', 'password', 'bind_phone', 'bind_phone_address', 'id', 'status', 'type', 'dsid'];

    public function logs(): HasMany
    {
        return $this->hasMany(AccountLogs::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Devices::class);
    }

//    public function payment(): HasMany
//    {
//        return $this->hasMany(Payment::class);
//    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getSessionId(): string
    {
        return md5(sprintf('%s_%s', $this->account, $this->password));
    }

    /**
     * Get the family that the account organizes
     */
    public function family(): HasOne
    {
        return $this->hasOne(Family::class, 'organizer', 'dsid');
    }

    /**
     * Get the family member record for this account
     */
    public function familyMember(): HasOne
    {
        return $this->hasOne(FamilyMember::class, 'apple_id', 'account');
    }

    /**
     * Get all family members where this account is the organizer
     */
    public function familyMembers(): HasManyThrough
    {
        return $this->hasManyThrough(
            FamilyMember::class,
            Family::class,
            'organizer', // Family 表中关联 Account 的外键
            'family_id', // FamilyMember 表中关联 Family 的外键
            'dsid',   // Account 表中的本地键
            'id'        // Family 表中的本地键
        );
    }

}
