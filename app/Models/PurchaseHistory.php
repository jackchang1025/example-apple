<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseHistory extends Model
{
    use HasFactory;

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
        return $this->hasMany(Pli::class);
    }
}
