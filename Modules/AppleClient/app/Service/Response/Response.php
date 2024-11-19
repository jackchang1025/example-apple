<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Response;


class Response extends \Saloon\Http\Response
{
    use HasServiceError;
    use HasAuth;
    use HasPhoneNumbers;
    use XmlResponseHandler;
}
