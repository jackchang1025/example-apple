<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @method static \Database\Factories\AccountLogsFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs query()
 * @property int $id
 * @property string $action
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs whereUpdatedAt($value)
 * @property int $phone_id
 * @property-read \App\Models\Account|null $account
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLogs wherePhoneId($value)
 * @mixin \Eloquent
 */
class AccountLogs extends Model
{
    use HasFactory;

    protected $table = 'account_logs';

    protected $fillable = ['account_id','action', 'description'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
