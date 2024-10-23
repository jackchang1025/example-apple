<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Phone\Services\PhoneNumberFactory;
use Modules\Phone\Services\PhoneService;
use Modules\PhoneCode\Service\PhoneCodeService;
use Modules\PhoneCode\Service\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;


/**
 *
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Phone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Phone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Phone query()
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $phone 手机号
 * @property string $phone_address 手机号地址
 * @property string $country_code 国家码
 * @property string $country_dial_code 区号
 * @property string $national_number 获取不包含国家代码的号码
 * @property string $status {normal:正常,invalid:失效,bound:已绑定}
 * @method static \Illuminate\Database\Eloquent\Builder|Phone whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone whereCountryDialCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone wherePhoneAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Phone whereUpdatedAt($value)
 * @property-read mixed $label
 * @method static \Database\Factories\PhoneFactory factory($count = null, $state = [])
 * @mixin \Eloquent
 */
class Phone extends Model
{
    use HasFactory;

    protected $table = 'phone';

    protected $fillable = ['phone','phone_address','country_code','country_dial_code','status'];

    //'{normal:正常,invalid:失效,bound:已绑定,Binding:绑定中}'
    const string STATUS_NORMAL = 'normal';
    const string STATUS_INVALID = 'invalid';
    const string STATUS_BOUND = 'bound';
    const string STATUS_BINDING = 'Binding';

    public const  array STATUS = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_INVALID => '失效',
        self::STATUS_BOUND => '已绑定',
        self::STATUS_BINDING => '绑定中',
    ];

    public const  array STATUS_COLOR = [
        self::STATUS_NORMAL => 'gray',
        self::STATUS_INVALID => 'warning',
        self::STATUS_BOUND => 'success',
        self::STATUS_BINDING => 'danger',
    ];

    protected function countryDialCode(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes) {
                return $this->getPhoneNumberService($attributes['phone'], $attributes['country_code'])->getCountryCode(
                );
            }
        );
    }

    protected function nationalNumber(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                return $this->getPhoneNumberService(
                    $attributes['phone'],
                    $attributes['country_code']
                )->getNationalNumber();
            }
        );
    }

    protected function countryCode(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes) {
                return $this->getPhoneNumberService($attributes['phone'], $value)->getCountry();
            }
        );
    }

    protected function label ():Attribute
    {
        return Attribute::make(
            get: function (?string $value = null, array $attributes = []) {
                return self::STATUS[$attributes['status']] ?? '未知';
            }
        );
    }

    /**
     * 获取 PhoneNumberService 实例
     *
     * @param string $phone
     * @param string|null $countryCode
     * @return PhoneService
     */
    public function getPhoneNumberService(string $phone, ?string $countryCode = null): PhoneService
    {
        return app(PhoneNumberFactory::class)->create($phone, [$countryCode]);
    }

    /**
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getPhoneCode(): Response
    {
        return app(PhoneCodeService::class)->getPhoneCode($this->phone_address);
    }
}
