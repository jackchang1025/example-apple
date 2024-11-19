<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property string $family_id 家庭组 ID
 * @property string $organizer 组织者的 Apple ID
 * @property string $etag 家庭组 etag 标识
 * @property array|null $transfer_requests 转移请求列表
 * @property array|null $invitations 邀请列表
 * @property array|null $outgoing_transfer_requests 发出的转移请求列表
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Account $account 组织者账号关联
 * @property-read \Illuminate\Database\Eloquent\Collection|FamilyMember[] $members 家庭成员关联
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|Family newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Family newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Family query()
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereEtag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereFamilyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereInvitations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereOrganizer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereOutgoingTransferRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereTransferRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Family whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Family extends Model
{
    protected $fillable = [
        'family_id',
        'organizer',
        'etag',
        'transfer_requests',
        'invitations',
        'outgoing_transfer_requests',
    ];

    protected $casts = [
        'transfer_requests'          => 'array',
        'invitations'                => 'array',
        'outgoing_transfer_requests' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'organizer', 'dsid');
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }
}
