<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pli extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'purchase_id',
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
        return $this->belongsTo(PurchaseHistory::class);
    }
}
