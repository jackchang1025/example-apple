<?php

namespace App\Apple\Service;


use Psr\Http\Message\ResponseInterface;

readonly class Response
{

    public function __construct(
        protected ResponseInterface $response,
        protected int $status,
        protected array $data
    )
    {
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }



    public function getStatus(): int
    {
        return $this->status;
    }

    public function getError(): ?array
    {
        return $this->data['serviceErrors'] ?? [];
    }

    public function getFirstError(): ?array
    {
        return $this->data['serviceErrors'][0] ?? [];
    }

    public function getFirstErrorMessage(): ?string
    {
        return $this->data['serviceErrors'][0]['message'] ?? null;
    }


    public function getData(?string $key = null): array
    {

        if ($key) {
            return $this->data[$key] ?? [];
        }
        return $this->data;
    }

    public function getTrustedPhoneNumbers()
    {
        return $this->data['trustedPhoneNumbers'] ?? [];
    }

    public function getPhoneNumber()
    {
        return $this->data['phoneNumber'] ?? [];
    }

    public function getSecurityCode()
    {
        return $this->data['securityCode'] ?? [];
    }

    public function getDevices():?string
    {
        return $this->data['mode'] ?? null;
    }

    public function getGuid():string
    {
        return uuid();
    }

    public function getId():?int
    {
        return$this->getPhoneNumber()['id'] ?? null;
    }

    public function getNumber():?string
    {
        return$this->getPhoneNumber()['obfuscatedNumber'] ?? null;
    }
}
