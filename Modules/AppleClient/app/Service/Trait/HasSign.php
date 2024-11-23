<?php

namespace Modules\AppleClient\Service\Trait;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\Sign\Sign;
use Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

trait HasSign
{
    protected ?Sign $sign = null;

    public function getSign(): ?Sign
    {
        return $this->sign;
    }

    public function isSign(): bool
    {
        return $this->getClient()->getHeaderRepositories()?->get('Authorization') !== null;
    }

    public function setSign()
    {
        return $this->getClient()->getHeaderRepositories()?->add('Authorization', true);
    }

    /**
     * @return Sign
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function sign(): Sign
    {
        if ($this->sign && $this->sign?->isValid()) {
            return $this->sign;
        }

        $response = $this->authLogin(
            $this->account->account,
            $this->account->password
        );

        $this->sign = Sign::fromResponse($response);

        return $this->sign;
    }

    /**
     * @return Sign
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function refreshSign(): Sign
    {
        $this->sign = Sign::fromResponse(
            $this->authLogin(
                $this->account->account,
                $this->account->password
            )
        );

        return $this->sign;
    }

    public function withSignData(?Sign $signData = null): static
    {
        $this->sign = $signData;

        return $this;
    }

    public function login()
    {
        $this->authLogin($this->account->account, $this->account->password);

        $phoneList = $this->auth()->getTrustedPhoneNumbers();

        $phone = $this->filterTrustedPhone($phoneList);
    }


    protected function attemptVerifyPhoneCode(DataCollection $phoneList): VerifyPhoneSecurityCode
    {

        for ($attempts = 1; $attempts <= $this->getTries(); $attempts++) {

            if ($verifyPhoneSecurityCode = $this->foreachPhoneListVerifyPhoneCode($phoneList, $attempts)) {
                return $verifyPhoneSecurityCode;
            }
        }

        throw new MaxRetryAttemptsException("最大尝试次数:{$this->tries}");
    }

    /**
     * @param DataCollection $trustedPhones
     * @return DataCollection
     * @throws PhoneNotFoundException
     * @throws \JsonException
     */
    protected function filterTrustedPhone(DataCollection $trustedPhones): DataCollection
    {
        $phoneList = $trustedPhones->filter(function (PhoneNumber $phone) {
            return Str::contains($this->getAccount()->bind_phone, $phone->lastTwoDigits);
        });

        if ($phoneList->count() === 0) {
            throw new PhoneNotFoundException(sprintf("%s:%s", '该账号未绑定该手机号码，无法授权登陆', json_encode([
                'account'       => $this->getAccount()->toArray(),
                'trusted_phone' => $trustedPhones->toArray(),
            ], JSON_THROW_ON_ERROR)));
        }

        return $phoneList;
    }

    /**
     * @return void
     * @throws AccountException
     * @throws PhoneAddressException
     */
    protected function validatePhoneBinding(): void
    {
        $account = $this->getAccount();

        if (empty($account->bind_phone)) {
            throw new AccountException("未绑定手机号");
        }

        if (empty($account->bind_phone_address)) {
            throw new AccountException("未绑定手机号地址");
        }

        if (!$this->validatePhoneAddress()) {
            throw new PhoneAddressException("手机号地址无效");
        }
    }

    /**
     * @return bool|null
     */
    protected function validatePhoneAddress(): ?bool
    {
        try {

            return Http::get($this->account->bind_phone_address)->successful();

        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * @param string $account
     * @param string $password
     *
     * @return Response
     *@throws \Saloon\Exceptions\Request\RequestException
     * @throws \JsonException
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function authLogin(string $account, string $password): Response
    {
        $initResponse = $this->getClient()->appleAuthInit($account);

        $signinInitResponse = $this->getClient()->init(a: $initResponse->json('value'), account: $account);

        $completeResponse = $this->getClient()->appleAuthComplete(
            key: $initResponse->json('key'),
            salt: $signinInitResponse->json('salt'),
            b: $signinInitResponse->json('b'),
            c: $signinInitResponse->json('c'),
            password: $password,
            iteration: $signinInitResponse->json('iteration'),
            protocol: $signinInitResponse->json('protocol')
        );

        return $this->getClient()->complete(
            account: $account,
            m1: $completeResponse->json('M1'),
            m2: $completeResponse->json('M2'),
            c: $completeResponse->json('c'),
        );
    }
}
