<?php

namespace App\Models;

use App\Apple\Service\Common;
use App\Apple\Service\PhoneCodeParser\PhoneCodeParserFactory;
use App\Apple\Service\PhoneCodeParser\PhoneCodeParserInterface;
use App\Apple\Service\PhoneNumber\PhoneNumberFactory;
use App\Apple\Service\PhoneNumber\PhoneNumberService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


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

    protected function countryDialCode(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes) {
                try {
                    $getCountryCode =  $this->getPhoneNumberService($attributes)->getCountryCode();
                    Log::info("countryDialCode :".$getCountryCode);
                    return $getCountryCode;
                } catch (\Exception $e) {
                    // 如果解析失败,返回原始值
                    Log::error("countryDialCode :".$e->getMessage());
                    return $value;
                }
            }
        );
    }

    protected function nationalNumber(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                return $this->getPhoneNumberService($attributes)->getNationalNumber();
            }
        );
    }

    protected function countryCode(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes) {

                try {
                    $phoneService = $this->getPhoneNumberService($attributes);
                    $regionCode = $phoneService->getRegionCode();
                    Log::info("Parsed region code: " . $regionCode);
                    return $regionCode;
                } catch (\Exception $e) {
                    Log::error("Error parsing phone number: " . $e->getMessage());
                    return $value;
                }
            }
        );
    }

//    protected function phone(): Attribute
//    {
//        return Attribute::make(
//            set: function ($value) {
//                $phoneService = app(PhoneNumberFactory::class)->createPhoneNumberService($value);
////                $this->attributes['country_code'] = $phoneService->getRegionCode();
////                $this->attributes['country_dial_code'] = $phoneService->getCountryCode();
//
//                $this->setAttribute('country_code', $phoneService->getRegionCode());
//                $this->setAttribute('country_dial_code', $phoneService->getCountryCode());
//
//                Log::info("phone :",['attributes' => $this->getAttributes()]);
//                return $phoneService->format();
//            }
//        );
//    }

    /**
     * 获取 PhoneNumberService 实例
     *
     * @param array $attributes
     * @return PhoneNumberService
     * @throws \InvalidArgumentException|\libphonenumber\NumberParseException
     */
    public function getPhoneNumberService(array $attributes): PhoneNumberService
    {
        return app(PhoneNumberFactory::class)->createPhoneNumberService($attributes['phone'] ?? '', $attributes['country_code'] ?? null);
    }

    /**
     * @throws \Exception
     */
    public function phoneCodeParser():PhoneCodeParserInterface
    {
        return app(PhoneCodeParserFactory::class)->create($this->phone_code_parser ?? 'default');
    }

}
