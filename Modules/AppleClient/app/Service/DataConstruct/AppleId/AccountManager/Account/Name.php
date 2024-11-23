<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account;

use Modules\AppleClient\Service\DataConstruct\Data;

class Name extends Data
{
    public function __construct(
        /** @var bool 是否需要中间名 */
        public bool $middleNameRequired,

        /** @var bool 是否支持名字发音 */
        public bool $supportsNamePronunciation,

        /** @var bool 是否要求姓氏在前 */
        public bool $lastNameFirstOrderingRequired,

        /** @var string 完整名字 */
        public string $fullName,

        /** @var string 名字 */
        public string $firstName,

        /** @var string 姓氏 */
        public string $lastName,

        /** @var string|null 中间名 */
        public ?string $middleName = null,

        /** @var string|null 名字发音 */
        public ?string $firstNamePronunciation = null,

        /** @var string|null 姓氏发音 */
        public ?string $lastNamePronunciation = null
    ) {
    }

    /**
     * 获取格式化的全名
     */
    public function getFormattedFullName(): string
    {
        if ($this->lastNameFirstOrderingRequired) {
            return trim("{$this->lastName} {$this->firstName}");
        }

        $parts = [$this->firstName];
        if ($this->middleName) {
            $parts[] = $this->middleName;
        }
        $parts[] = $this->lastName;

        return implode(' ', $parts);
    }

    /**
     * 检查名字是否完整
     */
    public function isComplete(): bool
    {
        if ($this->middleNameRequired && !$this->middleName) {
            return false;
        }

        return !empty($this->firstName) && !empty($this->lastName);
    }

    /**
     * 获取发音信息
     */
    public function getPronunciationInfo(): array
    {
        return [
            'supported' => $this->supportsNamePronunciation,
            'firstName' => $this->firstNamePronunciation,
            'lastName'  => $this->lastNamePronunciation,
        ];
    }

    /**
     * 获取名字部分
     */
    public function getNameParts(): array
    {
        return [
            'firstName'  => $this->firstName,
            'middleName' => $this->middleName,
            'lastName'   => $this->lastName,
        ];
    }
}
