<?php

namespace App\Apple\Service\Client\Middleware;

use App\Apple\Service\User\User;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

readonly class HeaderMiddleware implements GlobalMiddlewareInterface
{

    public function __construct(
        protected LoggerInterface $logger,
        protected User $user
    ) {
    }
    public function request(RequestInterface $request): RequestInterface
    {
        $headers = $this->user?->getHeaders();
        foreach ($headers as $name => $value) {
            if (!empty($value) && !$request->hasHeader($name) && $name === 'scnt') {
                $request = $request->withHeader($name, $value);
            }
        }

        return $request->withHeader('Referer', (string) $request->getUri())
            ->withHeader('account', $this->user->get('account'));
    }

    public function response(ResponseInterface $response): ResponseInterface
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                if ($name === 'scnt') {
                    $this->user->appendHeader('scnt', $value);
                }
                if (str_contains($name, 'X-Apple')) {
                    $this->user->appendHeader($name, $value);
                }
            }
        }

        $this->user->appendHeader('account' ,$this->user?->get('account'));

        return $response;
    }
}
