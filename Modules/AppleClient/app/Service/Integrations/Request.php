<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations;

use Modules\AppleClient\Service\Header\HasPersistentHeaders;
use Modules\AppleClient\Service\Proxy\HasProxy;
use Saloon\Http\Request as SaloonRequest;

abstract class Request extends SaloonRequest
{
    use HasProxy;
    use HasPersistentHeaders;
}
