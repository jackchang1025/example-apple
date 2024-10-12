<?php

namespace App\Rules;

use App\Apple\PhoneNumber\PhoneNumberFactory;
use App\Models\Phone;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class ValidPhoneNumber implements ValidationRule, DataAwareRule
{
    /**
     * 正在验证的所有数据。
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * 创建一个新的规则实例。
     */
    public function __construct(protected PhoneNumberFactory $phoneNumberFactory)
    {
    }

    /**
     * 运行验证规则。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
//        $countryCode = $this->data['country_code'] ?? 'US';

        try {
//            $phoneNumberService = $this->phoneNumberFactory->createPhoneNumberService($value, $countryCode);
            $phoneNumberService = $this->phoneNumberFactory->createPhoneNumberService($value);

            if (!$phoneNumberService->isValid()) {
                $fail("The {$attribute} is not a valid phone number for the specified country.");

                Log::error("The {$attribute} is not a valid phone number for the specified country.");//code: {$regionCode}
                return;
            }

//            $regionCode = $phoneNumberService->getRegionCode();
            $formattedNumber = $phoneNumberService->format();

            Log::info("Validating phone number: {$formattedNumber} for country ");//code: {$regionCode}


            if (Phone::where('phone', $formattedNumber)->exists()) {
                $fail("The phone number {$formattedNumber} already exists");
            }
        } catch (\Exception|\Throwable|\InvalidArgumentException $e) {
            $fail("Validating phone number: {$value} {$e->getMessage()} ");

            Log::error("Validating phone number: {$value} {$e->getMessage()} ");//code: {$regionCode}
        }
    }

    /**
     * 设置正在验证的数据。
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
