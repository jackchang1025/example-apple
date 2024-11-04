<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\ValidatePassword\ValidatePassword;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasValidatePassword
{
    protected ?ValidatePassword $validatePassword = null;

    public function withValidatePassword(?ValidatePassword $validatePasswordData): static
    {
        $this->validatePassword = $validatePasswordData;

        return $this;
    }

    /**
     * @return ValidatePassword
     * @throws \JsonException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getValidatePassword(): ValidatePassword
    {
        return $this->validatePassword ??= $this->validatePassword();
    }

    /**
     * @return ValidatePassword
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function refreshValidatePassword(): ValidatePassword
    {
        return $this->validatePassword = $this->validatePassword();
    }

    /**
     * @return ValidatePassword
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function validatePassword(): ValidatePassword
    {
        return ValidatePassword::fromResponse($this->getClient()->authenticatePassword($this->getAccount()->password));
    }
}
