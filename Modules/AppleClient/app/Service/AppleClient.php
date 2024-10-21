<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use Modules\AppleClient\Service\Config\HasConfig;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Helpers\Helpers;
use Modules\AppleClient\Service\Integrations\AppleAuth\AppleAuthConnector;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Logger\Logger;
use Modules\AppleClient\Service\Proxy\HasProxy;
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
    use Logger;
    use Conditionable;
    use HasTries;
    use HasMiddleware;

    protected AppleIdConnector $appleIdConnector;
    protected IdmsaConnector $idmsaConnector;
    protected AppleAuthConnector $appleAuthConnector;

    public function __construct()
    {
        $this->appleIdConnector = new AppleIdConnector($this);
        $this->idmsaConnector   = new IdmsaConnector($this);
        $this->appleAuthConnector = new AppleAuthConnector($this);
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
}
