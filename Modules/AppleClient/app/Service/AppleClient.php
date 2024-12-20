<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use Modules\AppleClient\Service\Config\HasConfig;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Helpers\Helpers;
use Modules\AppleClient\Service\Integrations\AppleAuth\AppleAuthConnector;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\Buy\BuyConnector;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Proxy\HasProxy;
use Modules\AppleClient\Service\Trait\HasFamily;
use Modules\AppleClient\Service\Trait\HasLogger;
use Modules\AppleClient\Service\Trait\HasLoginDelegates;
use Modules\AppleClient\Service\Trait\HasTries;
use Saloon\Traits\Conditionable;
use Saloon\Traits\Macroable;
use Saloon\Traits\RequestProperties\HasMiddleware;

class AppleClient
{
    use Macroable;
    use AppleId;
    use Idmsa;
    use AppleAuth;
    use HasConfig;
    use HasProxy;
    use HasCookie;
    use HasHeaderSynchronize;
    use Helpers;
    use HasLogger;
    use Conditionable;
    use HasTries;
    use HasMiddleware;

    protected AppleIdConnector $appleIdConnector;
    protected IdmsaConnector $idmsaConnector;
    protected AppleAuthConnector $appleAuthConnector;
    protected IcloudConnector $icloudConnector;
    protected BuyConnector $buyConnector;

    public function __construct()
    {
        $this->appleIdConnector = new AppleIdConnector($this);
        $this->idmsaConnector   = new IdmsaConnector($this);
        $this->appleAuthConnector = new AppleAuthConnector($this);
        $this->icloudConnector = new IcloudConnector($this);
        $this->buyConnector = new BuyConnector($this);
    }

    public function getAppleIdConnector(): AppleIdConnector
    {
        return $this->appleIdConnector;
    }

    public function getIdmsaConnector(): IdmsaConnector
    {
        return $this->idmsaConnector;
    }

    public function getAppleAuthConnector(): AppleAuthConnector
    {
        return $this->appleAuthConnector;
    }

    public function getIcloudConnector(): IcloudConnector
    {
        return $this->icloudConnector;
    }

    public function getBuyConnector(): BuyConnector
    {
        return $this->buyConnector;
    }


}
