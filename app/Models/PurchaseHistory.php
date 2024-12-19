<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 *
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pli> $plis
 * @property-read int|null $plis_count
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory query()
 * @property int $id
 * @property int $account_id 关联的账户ID
 * @property string $purchase_id 购买ID
 * @property string $web_order_id Web订单ID
 * @property string $dsid DSID
 * @property string|null $invoice_amount 发票金额
 * @property string|null $invoice_date 发票日期
 * @property string $purchase_date 购买日期
 * @property int $is_pending_purchase 是否为待处理购买
 * @property string|null $estimated_total_amount 预计总金额
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereDsid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereEstimatedTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereInvoiceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereIsPendingPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory wherePurchaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseHistory whereWebOrderId($value)
 * @mixin \Eloquent
 */
class PurchaseHistory extends Model
{
    use HasFactory;

    protected $table = 'purchase_history';

    protected $fillable = [
        'account_id',           // 关联的账户ID
        'purchase_id',
        'dsid',
        'invoice_amount',
        'weborder_id',
        'invoice_date',
        'purchase_date',
        'is_pending_purchase',
        'estimated_total_amount',
    ];

    /**
     * 获取与购买相关的 PLIs。
     *
     * @return HasMany
     */
    public function plis(): HasMany
    {
        return $this->hasMany(Pli::class, 'purchase_id', 'purchase_id');
    }

    protected function invoiceDate(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value = null) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    protected function purchaseDate(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value = null) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    //purchase_date
}
