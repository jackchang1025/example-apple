<?php

namespace Modules\AppleClient\Service\Resources\Web\AppleId;

use Modules\AppleClient\Events\AccountBindPhoneFailEvent;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;
use Modules\AppleClient\Service\DataConstruct\AddSecurityVerifyPhone\AddSecurityVerifyPhoneInterface;
use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class SecurityPhoneResource
{
    use HasTries;

    public function __construct(
        protected AppleIdResource $appleIdResource,
        protected PhoneCodeParser $phoneCodeParser = new PhoneCodeParser()
    ) {
        // 设置默认重试次数和重试间隔
        $this->withTries(5)
            ->withRetryInterval(1000)
            ->withUseExponentialBackoff();
    }

    public function getAppleIdResource(): AppleIdResource
    {
        return $this->appleIdResource;
    }

    /**
     * @param string $countryCode
     * @param string $phoneNumber
     * @param string $countryDialCode
     * @param bool $nonFTEU
     * @return SecurityVerifyPhone
     * @throws \Modules\AppleClient\Service\Exception\BindPhoneException
     * @throws \Modules\AppleClient\Service\Exception\ErrorException
     * @throws \Modules\AppleClient\Service\Exception\PhoneException
     * @throws \Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException
     * @throws \Modules\AppleClient\Service\Exception\StolenDeviceProtectionException
     * @throws \Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function securityVerifyPhone(
        string $countryCode,
        string $phoneNumber,
        string $countryDialCode,
        bool $nonFTEU = true
    ): SecurityVerifyPhone {
        return $this->getAppleIdResource()
            ->getAppleIdConnector()
            ->getSecurityPhoneResources()
            ->securityVerifyPhone(
                countryCode: $countryCode,
                phoneNumber: $phoneNumber,
                countryDialCode: $countryDialCode,
                nonFTEU: $nonFTEU
            );
    }

    /**
     * @param AddSecurityVerifyPhoneInterface $addSecurityVerifyPhone
     * @return SecurityVerifyPhone
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws BindPhoneException
     * @throws ErrorException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws AttemptBindPhoneCodeException
     * @throws FatalRequestException
     */
    public function addSecurityVerifyPhone(AddSecurityVerifyPhoneInterface $addSecurityVerifyPhone): SecurityVerifyPhone
    {

        if (!$phoneCodeService = $this->getAppleIdResource()->getPhoneCodeService()) {
            throw new \RuntimeException('phoneCodeService is not null');
        }

        try {

            $response = $this->securityVerifyPhone(
                countryCode: $addSecurityVerifyPhone->getCountryCode(),
                phoneNumber: $addSecurityVerifyPhone->getPhoneNumber(),
                countryDialCode: $addSecurityVerifyPhone->getCountryDialCode(),
            );

            //为了防止拿到上一次验证码导致错误，这里建议睡眠一段时间再尝试
            usleep($this->getSleepTime(1, $this->getRetryInterval(), false));

            $code = $phoneCodeService->attemptGetPhoneCode(
                $addSecurityVerifyPhone->getPhoneAddress(),
                $this->phoneCodeParser
            );

            $response = $this->securityVerifyPhoneSecurityCode(
                id: $response->phoneNumberVerification->phoneNumber->id,
                phoneNumber: $addSecurityVerifyPhone->getPhoneNumber(),
                countryCode: $addSecurityVerifyPhone->getCountryCode(),
                countryDialCode: $addSecurityVerifyPhone->getCountryDialCode(),
                code: $code
            );

            //执行事件
            $this->getAppleIdResource()
                ->getWebResource()
                ->getApple()
                ->getDispatcher()
                ?->dispatch(
                    new AccountBindPhoneSuccessEvent(
                        account: $this->getAppleIdResource()->getWebResource()->getApple()->getAccount(),
                        addSecurityVerifyPhone: $addSecurityVerifyPhone,
                        message: "{$addSecurityVerifyPhone->getPhoneNumber()} 绑定成功"
                    )
                );

            return $response;
        } catch (\Exception $e) {

            //执行事件
            $this->getAppleIdResource()
                ->getWebResource()
                ->getApple()
                ->getDispatcher()
                ?->dispatch(
                    new AccountBindPhoneFailEvent(
                        account: $this->getAppleIdResource()->getWebResource()->getApple()->getAccount(),
                        addSecurityVerifyPhone: $addSecurityVerifyPhone,
                        message: "{$addSecurityVerifyPhone->getPhoneNumber()} 绑定失败,原因:{$e->getMessage()}"
                    )
                );

            throw $e;
        }
    }

    /**
     * @param int $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     * @return SecurityVerifyPhone
     * @throws VerificationCodeException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function securityVerifyPhoneSecurityCode(
        int $id,
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        string $code
    ): SecurityVerifyPhone {
        try {

            return $this->getAppleIdResource()
                ->getAppleIdConnector()
                ->getSecurityPhoneResources()
                ->securityVerifyPhoneSecurityCode(
                    id: $id,
                    phoneNumber: $phoneNumber,
                    countryCode: $countryCode,
                    countryDialCode: $countryDialCode,
                    code: $code
                );

        } catch (RequestException $e) {

            throw new VerificationCodeException($e->getResponse());
        }
    }


    /**
     * @param AddSecurityVerifyPhoneInterface $addSecurityVerifyPhone
     * @return SecurityVerifyPhone
     * @throws \Throwable
     */
    public function addSecurityVerifyPhoneWithRetry(AddSecurityVerifyPhoneInterface $addSecurityVerifyPhone
    ): SecurityVerifyPhone {
        return $this->handleRetry(fn() => $this->addSecurityVerifyPhone($addSecurityVerifyPhone));
    }
}
