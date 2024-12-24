<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property string|null $payment_id
 * @property int $account_id
 * @property string $payment_method_name
 * @property string|null $payment_method_detail
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $partner_login
 * @property array|null $phone_number
 * @property array|null $owner_name
 * @property array|null $billing_address
 * @property string|null $payment_account_country_code
 * @property string $type
 * @property bool $is_primary
 * @property bool $we_chat_pay
 * @property string|null $absolute_image_path
 * @property string|null $absolute_image_path_2x
 * @property bool $payment_supported
 * @property bool $family_card
 * @property bool $expiration_supported
 * @property array|null $payment_method_option
 * @property-read \App\Models\Account $account
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAbsoluteImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAbsoluteImagePath2x($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereBillingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereExpirationSupported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereFamilyCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereOwnerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePartnerLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentAccountCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethodDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethodName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethodOption($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentSupported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereWeChatPay($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * @var int $id 主键ID
     * @var int $account_id 关联的账户ID
     * @var string|null $payment_id 支付ID
     * @var string $payment_method_name 支付方式名称
     * @var string $payment_method_detail 支付方式详情
     * @var string|null $partner_login 合作伙伴登录信息
     * @var array|null $phone_number 电话号码信息（JSON格式）
     * @var array|null $owner_name 所有者姓名信息（JSON格式）
     * @var array|null $billing_address 账单地址信息（JSON格式）
     * @var string|null $payment_account_country_code 支付账户国家代码
     * @var string $type 支付方式类型
     * @var bool $is_primary 是否为主要支付方式
     * @var bool $we_chat_pay 是否为微信支付
     * @var string|null $absolute_image_path 图片路径
     * @var string|null $absolute_image_path_2x 高清图片路径
     * @var bool $payment_supported 是否支持支付
     * @var bool $family_card 是否为家庭卡
     * @var bool $expiration_supported 是否支持过期
     * @var array|null $payment_method_option 支付方式选项（JSON格式）
     * @var \Carbon\Carbon $created_at 创建时间
     * @var \Carbon\Carbon $updated_at 更新时间
     */
    protected $fillable = [
        'account_id',
        'payment_id',
        'payment_method_name',
        'payment_method_detail',
        'partner_login',
        'phone_number',
        'owner_name',
        'billing_address',
        'payment_account_country_code',
        'type',
        'is_primary',
        'we_chat_pay',
        'absolute_image_path',
        'absolute_image_path_2x',
        'payment_supported',
        'family_card',
        'expiration_supported',
        'payment_method_option',
        'default_shipping_address',
    ];

    protected $casts = [
        'phone_number'          => 'array',
        'owner_name'            => 'array',
        'billing_address'       => 'array',
        'is_primary'            => 'boolean',
        'we_chat_pay'           => 'boolean',
        'payment_supported'     => 'boolean',
        'family_card'           => 'boolean',
        'expiration_supported'  => 'boolean',
        'payment_method_option' => 'array',
        'default_shipping_address' => 'array',
    ];


    /**
     * 获取关联的账户
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }


}
