<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\Sign\Sign;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasSign
{
    protected ?Sign $sign = null;


    public function getSign(): ?Sign
    {
        return $this->sign;
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
