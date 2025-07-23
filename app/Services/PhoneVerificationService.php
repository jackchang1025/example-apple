<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Phone;
use App\Services\Integrations\Phone\PhoneRequest;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;

/**
 * 手机验证服务
 * 负责处理手机号验证的具体流程
 */
class PhoneVerificationService
{
    private const MAX_CODE_ATTEMPTS = 5;

    public function __construct(
        private readonly Account $account
    ) {}

    /**
     * 执行手机验证流程
     *
     * @param Phone $phone
     * @return SecurityVerifyPhone
     * @throws \Exception
     */
    public function verify(Phone $phone): SecurityVerifyPhone
    {
        // 发起手机验证
        $response = $this->initializeVerification($phone);

        // 创建手机请求对象
        $phoneRequest = $this->createPhoneRequest($phone);

        // 完成验证码验证
        return $this->completeVerification(
            $phoneRequest,
            $response->phoneNumberVerification->phoneNumber->id,
            $phone
        );
    }

    /**
     * 初始化手机验证
     *
     * @param Phone $phone
     * @return SecurityVerifyPhone
     */
    private function initializeVerification(Phone $phone): SecurityVerifyPhone
    {
        return $this->account->appleIdResource()
            ->getSecurityPhoneResource()
            ->securityVerifyPhone(
                countryCode: $phone->country_code,
                phoneNumber: $phone->format(),
                countryDialCode: $phone->country_dial_code
            );
    }

    /**
     * 创建手机请求对象
     *
     * @param Phone $phone
     * @return PhoneRequest
     */
    private function createPhoneRequest(Phone $phone): PhoneRequest
    {
        $phoneRequest = $phone->makePhoneRequest();

        if ($this->account->debug()) {
            $phoneRequest->debug();
        }

        $phoneRequest->middleware()->merge($this->account->middleware());

        return $phoneRequest;
    }

    /**
     * 完成验证码验证
     *
     * @param PhoneRequest $phoneRequest
     * @param int $verificationId
     * @param Phone $phone
     * @return SecurityVerifyPhone
     * @throws VerificationCodeException
     */
    private function completeVerification(PhoneRequest $phoneRequest, int $verificationId, Phone $phone): SecurityVerifyPhone
    {
        for ($attempt = 1; $attempt <= self::MAX_CODE_ATTEMPTS; $attempt++) {
            try {
                
                // $code = $phoneRequest->attemptMobileVerificationCode();
                $code = rand(100000, 999999);
                
                return $this->submitVerificationCode($verificationId, $code, $phone);
            } catch (VerificationCodeException $e) {
               
            }
        }

        throw new VerificationCodeException('验证码验证失败');
    }

    /**
     * 提交验证码
     *
     * @param int $verificationId
     * @param string $code
     * @param Phone $phone
     * @return SecurityVerifyPhone
     */
    private function submitVerificationCode(int $verificationId, string $code, Phone $phone): SecurityVerifyPhone
    {
        return $this->account->appleIdResource()
            ->getSecurityPhoneResource()
            ->securityVerifyPhoneSecurityCode(
                id: $verificationId,
                phoneNumber: $phone->format(),
                countryCode: $phone->country_code,
                countryDialCode: $phone->country_dial_code,
                code: $code
            );
    }
}
