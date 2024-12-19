<?php

namespace App\Models;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property-read \App\Models\AccountManager|null $accountManager
 * @property-read \App\Models\FamilyMember|null $asFamilyMember
 * @property-read \App\Models\Family|null $belongToFamily
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PhoneNumber> $phoneNumbers
 * @property-read int|null $phone_numbers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IcloudDevice> $IcloudDevice
 * @property-read int|null $icloud_device_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseHistory> $purchaseHistory
 * @property-read int|null $purchase_history_count
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

    public function IcloudDevice(): HasMany
    {
        return $this->hasMany(IcloudDevice::class);
    }

//    public function payment(): HasMany
//    {
//        return $this->hasMany(Payment::class);
//    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function purchaseHistory(): HasMany
    {
        return $this->HasMany(PurchaseHistory::class);
    }

    public function getSessionId(): string
    {
        return md5(sprintf('%s_%s', $this->account, $this->password));
    }

    /**
     * 获取账号所属的家庭组（无论是否为组织者）
     */
    public function belongToFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'dsid', 'organizer')
            ->orWhereHas('members', function ($query) {
                $query->where('dsid', $this->dsid)
                    ->orWhere('apple_id', $this->account);
            });
    }

    /**
     * 获取同一家庭组的所有成员（无论是否为组织者）
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_id', 'id')
            ->whereHas('family', function ($query) {
                $query->where('organizer', $this->dsid);
            })
            ->orWhereExists(function ($query) {
                $query->from('family_members as fm')
                    ->whereColumn('fm.family_id', 'family_members.family_id')
                    ->where(function ($q) {
                        $q->where('fm.dsid', $this->dsid)
                            ->orWhere('fm.apple_id', $this->account);
                    });
            });
    }

    /**
     * 获取当前账号的家庭成员记录
     */
    public function asFamilyMember(): HasOne
    {
        return $this->hasOne(FamilyMember::class, 'dsid', 'dsid')
            ->orWhere('apple_id', $this->account);
    }

    /**
     * 判断是否为家庭组织者
     */
    public function isFamilyOrganizer(): bool
    {
        return $this->family()->exists();
    }

    /**
     * 获取账号关联的所有电话号码
     */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function accountManager(): HasOne
    {
        return $this->hasOne(AccountManager::class);
    }

    /**
     * 获取所有相关的家庭成员ID（用于调试）
     */
    public function getAllFamilyMemberIds(): array
    {
        // 作为组织者的家庭成员
        $asOrganizerMembers = FamilyMember::query()
            ->whereHas('family', function ($query) {
                $query->where('organizer', $this->dsid);
            })
            ->pluck('id')
            ->toArray();

        // 作为成员的同组成员
        $asMemberFamilyIds = FamilyMember::query()
            ->where(function ($query) {
                $query->where('dsid', $this->dsid)
                    ->orWhere('apple_id', $this->account);
            })
            ->pluck('family_id')
            ->toArray();

        $asMemberMembers = FamilyMember::query()
            ->whereIn('family_id', $asMemberFamilyIds)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($asOrganizerMembers, $asMemberMembers));
    }

    public function toAccount(): \Modules\AppleClient\Service\DataConstruct\Account
    {
        return \Modules\AppleClient\Service\DataConstruct\Account::from($this->toArray());
    }

}
