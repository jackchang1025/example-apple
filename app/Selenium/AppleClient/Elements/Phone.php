<?php

namespace App\Selenium\AppleClient\Elements;

use App\Apple\Service\PhoneNumber\PhoneNumberFactory;
use App\Apple\Service\PhoneNumber\PhoneNumberService;
use Facebook\WebDriver\WebDriverElement;
use JsonSerializable;
use Serializable;

class Phone implements JsonSerializable,Serializable
{
    protected int|string|null $phone;

    protected ?WebDriverElement $element = null;

    public function __construct(
        protected int $id,
        WebDriverElement $element
    ) {
        $this->element = $element;
        $this->phone = $element->getText();
    }

    public function setElement(?WebDriverElement $element = null): void
    {
        $this->element = $element;
    }

    public function getElement(): WebDriverElement
    {
        return $this->element;
    }

    public function getPhone(): int|string|null
    {
        return $this->phone;
    }

    public function setPhone(int|string|null $phone): void
    {
        $this->phone = $phone;
    }



    public function getPhoneNumberService(): PhoneNumberService
    {
        return app(PhoneNumberFactory::class)->createPhoneNumberService($this->phone);
    }

    /**
     * 获取电话号码 ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
        ];
    }

    public function serialize(): string
    {
        return serialize([
            'id' => $this->id,
            'phone' => $this->phone,
        ]);
    }

    public function unserialize(string $data): void
    {
        $unserialized = unserialize($data);
        $this->id = $unserialized['id'];
        $this->phone = $unserialized['phone'];
        $this->element = null; // WebDriverElement is not serialized
    }

    public function __serialize(): array
    {
        return [$this->serialize()];
    }

    public function __unserialize(array $data): void
    {
        $this->unserialize($data[0]);
    }
}


