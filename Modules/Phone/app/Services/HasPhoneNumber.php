<?php

namespace Modules\Phone\Services;

use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\Exceptions\NumberFormatException;

trait HasPhoneNumber
{
    public function formatAccount(string $accountName): string
    {
        $validator = Validator::make(['email' => $accountName], [
            'email' => 'email',
        ]);

        // 不是有效的邮箱,那就是手机号
        if ($validator->fails()) {
            return $this->formatPhone($accountName);
        }

        return $accountName;
    }

    public function formatPhone(string $accountName): ?string
    {
        try {

            return $this->phoneService($accountName)->format();

        } catch (NumberFormatException $e) {

            return $accountName;
        }
    }

    public function phoneService(
        string $phone,
        ?array $countryCode = null,
        ?int $phoneNumberFormat = null
    ): PhoneService {
        return (new PhoneNumberFactory())->create($phone, $countryCode, $phoneNumberFormat);
    }
}
