<?php

namespace Modules\AppleClient\Service;

use App\Models\Account;
use Modules\AppleClient\Service\Trait\HasAuth;
use Modules\AppleClient\Service\Trait\HasBindPhone;
use Modules\AppleClient\Service\Trait\HasDevices;
use Modules\AppleClient\Service\Trait\HasFamily;
use Modules\AppleClient\Service\Trait\HasLoginDelegates;
use Modules\AppleClient\Service\Trait\HasNotification;
use Modules\AppleClient\Service\Trait\HasPayment;
use Modules\AppleClient\Service\Trait\HasSign;
use Modules\AppleClient\Service\Trait\HasToken;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\AppleClient\Service\Trait\HasValidatePassword;
use Modules\AppleClient\Service\Trait\HasValidateStolenDeviceProtection;
use Modules\AppleClient\Service\Trait\HasVerifyCode;
use Modules\Phone\Services\HasPhoneNumber;
use Modules\PhoneCode\Service\PhoneCodeService;

/**
 * @mixin AppleClient
 */
class AppleAccountManager
{
    use HasSign;
    use HasAuth;
    use HasTries;
    use HasBindPhone;
    use HasNotification;
    use HasPayment;
    use HasDevices;
    use HasVerifyCode;
    use HasValidateStolenDeviceProtection;
    use HasToken;
    use HasValidatePassword;
    use HasPhoneNumber;
    use HasLoginDelegates;
    use HasFamily;

    public function __construct(
        protected Account $account,
        protected AppleClient $client,
        protected PhoneCodeService $phoneCodeService
    ) {
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * Retrieves the phone connector instance.
     *
     * This method returns the currently set phone connector object which can be used for further communication operations.
     *
     * @return PhoneCodeService The phone connector instance configured for the application.
     */
    public function getPhoneCodeService(): PhoneCodeService
    {
        return $this->phoneCodeService;
    }

    public function withPhoneConnector(PhoneCodeService $phoneConnector): static
    {
        $this->phoneCodeService = $phoneConnector;

        return $this;
    }

    /**
     * Retrieves the instance of the AppleClient.
     *
     * Returns the configured AppleClient instance which can be used for further interactions
     * with Apple services.
     *
     * @return AppleClient The configured instance of the AppleClient.
     */
    public function getClient(): AppleClient
    {
        return $this->client;
    }

    /**
     * Handles dynamic method calls to the apple client service.
     *
     * This magic method allows the invocation of methods on the apple client service instance
     * by dynamically passing the method name and its parameters. It acts as a passthrough,
     * forwarding the call to the underlying apple client service with the provided arguments.
     *
     * @param string $name The name of the method to be called dynamically.
     * @param array $parameters An array of parameters to be passed to the method call.
     *
     * @return mixed The result of the method call on the apple client service, if any.
     */
    public function __call(string $name, array $parameters)
    {
        return $this->getClient()->$name(...$parameters);
    }
}
