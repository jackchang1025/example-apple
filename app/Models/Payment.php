<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'display_name',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
