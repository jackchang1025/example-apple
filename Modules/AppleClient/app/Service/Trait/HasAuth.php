<?php

namespace Modules\AppleClient\Service\Trait;

use Exception;
use Modules\AppleClient\Service\DataConstruct\Auth\Auth;
use Modules\AppleClient\Service\Exception\AccountLockoutException;
use Modules\AppleClient\Service\Exception\AppleClientException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasAuth
{
    protected ?Auth $auth = null;

    public function withAuth(?Auth $authData): static
    {
        $this->auth = $authData;

        return $this;
    }

    /**
     * @return Auth
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function auth(): Auth
    {
        return $this->auth ??= Auth::fromResponse($this->getClient()->auth());
    }

    /**
     * @return Auth
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function refreshAuth(): Auth
    {
        return $this->auth = Auth::fromResponse($this->getClient()->auth());
    }
}
