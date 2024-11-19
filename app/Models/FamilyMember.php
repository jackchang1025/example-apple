<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $family_id 关联的家庭组ID
 * @property string $last_name 姓氏
 * @property string $dsid Apple DSID
 * @property string $original_invitation_email 初始邀请邮箱
 * @property string $full_name 全名
 * @property string $age_classification 年龄分类
 * @property string $apple_id_for_purchases 用于购买的 Apple ID
 * @property string $apple_id Apple ID
 * @property string $first_name 名字
 * @property string $dsid_for_purchases 用于购买的 DSID
 * @property bool $has_parental_privileges 是否有家长权限
 * @property bool $has_screen_time_enabled 是否启用屏幕使用时间
 * @property bool $has_ask_to_buy_enabled 是否启用购买请求
 * @property bool $has_share_purchases_enabled 是否启用购买项目共享
 * @property bool $has_share_my_location_enabled 是否启用位置共享
 * @property array|null $share_my_location_enabled_family_members 启用位置共享的家庭成员列表
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Family $family 家庭组关联
 * @property-read Account $account 账号关联
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember query()
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereAgeClassification($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereAppleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereAppleIdForPurchases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereDsid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereDsidForPurchases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereFamilyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereHasAskToBuyEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereHasParentalPrivileges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereHasScreenTimeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereHasShareMyLocationEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereHasSharePurchasesEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereOriginalInvitationEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereShareMyLocationEnabledFamilyMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FamilyMember whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FamilyMember extends Model
{
    protected $fillable = [
        'last_name',
        'family_id',
        'dsid',
        'original_invitation_email',
        'full_name',
        'age_classification',
        'apple_id_for_purchases',
        'apple_id',
        'first_name',
        'dsid_for_purchases',
        'has_parental_privileges',
        'has_screen_time_enabled',
        'has_ask_to_buy_enabled',
        'has_share_purchases_enabled',
        'has_share_my_location_enabled',
        'share_my_location_enabled_family_members',
    ];

    protected $casts = [
        'has_parental_privileges'                  => 'boolean',
        'has_screen_time_enabled'                  => 'boolean',
        'has_ask_to_buy_enabled'                   => 'boolean',
        'has_share_purchases_enabled'              => 'boolean',
        'has_share_my_location_enabled'            => 'boolean',
        'share_my_location_enabled_family_members' => 'array',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'apple_id', 'account');
    }
}
