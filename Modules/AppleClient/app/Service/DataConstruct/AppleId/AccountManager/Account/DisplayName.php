<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account;

use Modules\AppleClient\Service\DataConstruct\Data;

class DisplayName extends Data
{
    public function __construct(
        /** @var string 名字 */
        public string $firstName,

        /** @var string 昵称 */
        public string $nickName,

        /** @var string 姓氏 */
        public string $lastName,

        /** @var bool 是否要求姓氏在前 */
        public bool $lastNameFirstOrderingRequired,

        /** @var bool 名字中是否不需要空格 */
        public bool $noSpaceRequiredInName,

        /** @var string 完整的发音名字 */
        public string $fullPronounceName,

        /** @var string 完整名字 */
        public string $fullName
    ) {
    }

    /**
     * 获取显示名称
     */
    public function getDisplayName(string $format = 'full'): string
    {
        return match ($format) {
            'nickname' => $this->nickName,
            'formal' => $this->getFormattedFullName(),
            'full' => $this->fullName,
            default => $this->nickName,
        };
    }

    /**
     * 获取格式化的全名
     */
    private function getFormattedFullName(): string
    {
        if ($this->lastNameFirstOrderingRequired) {
            return $this->noSpaceRequiredInName
                ? "{$this->lastName}{$this->firstName}"
                : "{$this->lastName} {$this->firstName}";
        }

        return $this->noSpaceRequiredInName
            ? "{$this->firstName}{$this->lastName}"
            : "{$this->firstName} {$this->lastName}";
    }

    /**
     * 检查是否有发音信息
     */
    public function hasPronunciation(): bool
    {
        return !empty($this->fullPronounceName);
    }

    /**
     * 获取名字组件
     */
    public function getNameComponents(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'nickName'  => $this->nickName,
        ];
    }

    /**
     * 检查昵称是否与真实名字不同
     */
    public function hasCustomNickname(): bool
    {
        return $this->nickName !== $this->firstName;
    }
}
