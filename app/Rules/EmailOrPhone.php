<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
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

        // If it's not a valid email, check if it's a valid phone number
        try {
            $phone = new PhoneNumber ($value,'CN');
            if (!$phone->isValid()) {
                $fail('The :attribute must be a valid email address or phone number.');
            }
        } catch (\Exception $e) {
            $fail('The :attribute must be a valid email address or phone number.');
        }
    }
}
