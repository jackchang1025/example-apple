<?php

namespace App\Rules;

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

class EmailOrPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emailValidator = Validator::make([$attribute => $value], [
            $attribute => 'email:rfc,dns'
        ]);

        if ($emailValidator->passes()) {
            return; // It's a valid email, so we're done
        }

        $countryCode = SecuritySetting::first()?->configuration['country_code'] ?? 'CN';

        // If it's not a valid email, check if it's a valid phone number
        try {
            $phone = new PhoneNumber ($value,$countryCode);
            if (!$phone->isValid()) {
                $fail(':attribute 必须是一个有效的电子邮件地址或电话号码.');
            }
        } catch (\Exception $e) {
            $fail(':attribute 必须是一个有效的电子邮件地址或电话号码.');
        }
    }
}
