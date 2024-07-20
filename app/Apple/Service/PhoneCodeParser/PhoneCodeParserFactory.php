<?php

namespace App\Apple\Service\PhoneCodeParser;

use Illuminate\Contracts\Container\Container;

class PhoneCodeParserFactory
{
    protected array $phoneCodeParsers = [
        'default' => DefaultParser::class,
    ];

    public function __construct(protected Container $container)
    {
    }

    /**
     * @param string $phoneCode
     * @return PhoneCodeParserInterface
     * @throws \Exception
     */
    public function create(string $phoneCode = 'default'): PhoneCodeParserInterface
    {
        if (isset($this->phoneCodeParsers[$phoneCode])) {
            return $this->container->make($this->phoneCodeParsers[$phoneCode]);
        }

        throw new \Exception('PhoneCodeParser not found');
    }
}
