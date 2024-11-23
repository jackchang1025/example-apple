<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $account_id 关联的账号ID
 * @property string $raw_number 原始号码
 * @property string $country_code 国家代码
 * @property string $country_dial_code 国家拨号代码
 * @property string $full_number_with_prefix 带国家代码的完整号码
 * @property string $full_number_without_prefix 不带国家代码的号码
 * @property bool $is_vetted 是否已验证
 * @property bool $is_trusted 是否为可信号码
 * @property bool $is_login_handle 是否为登录句柄
 * @property string $type 电话类型
 * @property array|null $additional_data 额外数据
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber query()
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereCountryDialCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereFullNumberWithPrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereFullNumberWithoutPrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereIsLoginHandle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereIsTrusted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereIsVetted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereRawNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneNumber whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'raw_number',
        'country_code',
        'country_dial_code',
        'full_number_with_prefix',
        'full_number_without_prefix',
        'is_vetted',
        'is_trusted',
        'is_login_handle',
        'type',
        'additional_data',
    ];

    protected $casts = [
        'is_vetted'       => 'boolean',
        'is_trusted'      => 'boolean',
        'is_login_handle' => 'boolean',
        'additional_data' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
