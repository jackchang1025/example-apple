<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 *
 *
 * @property int $id
 * @property int $purchase_history_id 购买ID
 * @property string $item_id 项目ID
 * @property string $storefront_id 商店前端ID
 * @property string $adam_id Adam ID
 * @property string $guid GUID
 * @property string $amount_paid 支付金额
 * @property string $pli_date PLI日期
 * @property int $is_free_purchase 是否为免费购买
 * @property int $is_credit 是否为信用
 * @property string $line_item_type 行项目类型
 * @property string|null $title 标题
 * @property string|null $localized_content 本地化内容
 * @property string|null $subscription_info 订阅信息
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PurchaseHistory|null $purchase
 * @method static \Illuminate\Database\Eloquent\Builder|Pli newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Pli newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Pli query()
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereAdamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereGuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereIsCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereIsFreePurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereLineItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereLocalizedContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli wherePliDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli wherePurchaseHistoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereStorefrontId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereSubscriptionInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pli whereUpdatedAt($value)
 * @property int $purchase_id 购买ID
 * @method static \Illuminate\Database\Eloquent\Builder|Pli wherePurchaseId($value)
 * @mixin \Eloquent
 */
class Pli extends Model
{
    use HasFactory;

    protected $casts = [
        'localized_content' => 'array',
        'subscription_info' => 'array',
    ];

    protected $fillable = [
        'item_id',
        'purchase_id',
        'purchase_history_id',
        'storefront_id',
        'adam_id',
        'guid',
        'amount_paid',
        'pli_date',
        'is_free_purchase',
        'is_credit',
        'line_item_type',
        'title',
        'localized_content',
        'subscription_info',
    ];

    /**
     * 获取与 PLI 相关的购买。
     *
     * @return BelongsTo
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PurchaseHistory::class, 'purchase_history_id');
    }

    protected function pliDate(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value = null) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }
}
