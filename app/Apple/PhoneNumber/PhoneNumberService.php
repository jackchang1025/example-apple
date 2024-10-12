<?php

namespace App\Apple\PhoneNumber;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * 电话号码服务类
 *
 * 该类提供了一系列方法来处理和验证特定的电话号码。
 */
class PhoneNumberService
{
    /** @var PhoneNumberUtil 电话号码工具实例 */
    private PhoneNumberUtil $phoneUtil;

    /** @var PhoneNumber|null 解析后的电话号码对象 */
    private ?PhoneNumber $phoneNumber;

    /**
     * 构造函数
     *
     * @param string $phoneNumber 要处理的电话号码
     * @param string|null $countryCode 国家代码（默认为 'US'）
     * @param int $phoneNumberFormat 电话号码格式（默认为 E164）
     * @throws \InvalidArgumentException|NumberParseException 如果电话号码无效
     */
    public function __construct(
        string $phoneNumber,
        ?string $countryCode = null,
        private readonly int $phoneNumberFormat = PhoneNumberFormat::E164
    ) {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $this->parsePhoneNumber($phoneNumber, $countryCode);
    }

    /**
     * 解析电话号码
     *
     * @param string $phoneNumber 要解析的电话号码
     * @param string|null $countryCode 国家代码
     * @throws \InvalidArgumentException|NumberParseException 如果电话号码无效
     */
    private function parsePhoneNumber(string $phoneNumber, ?string $countryCode = null): void
    {
        $this->phoneNumber = $this->phoneUtil->parse($phoneNumber, $countryCode);
        if (!$this->phoneUtil->isValidNumber($this->phoneNumber)) {
            throw new NumberParseException(NumberParseException::INVALID_COUNTRY_CODE,"无效的电话号码：{$phoneNumber}");
        }
    }

    /**
     * 验证电话号码是否有效
     *
     * @return bool 如果号码有效则返回 true，否则返回 false
     */
    public function isValid(): bool
    {
        return $this->phoneUtil->isValidNumber($this->phoneNumber);
    }

    /**
     * 格式化电话号码
     *
     * @param int|null $numberFormat 格式化类型，如果为 null 则使用构造函数中设置的格式
     * @return string 格式化后的电话号码字符串
     */
    public function format(int $numberFormat = null): string
    {
        return $this->phoneUtil->format($this->phoneNumber, $numberFormat ?? $this->phoneNumberFormat);
    }

    /**
     * 获取电话号码的国家/地区代码
     *
     * @return string 国家/地区代码
     */
    public function getRegionCode(): string
    {
        return $this->phoneUtil->getRegionCodeForNumber($this->phoneNumber);
    }

    /**
     * 检查给定的国家/地区代码是否被支持
     *
     * @param string $regionCode 国家/地区代码
     * @return bool 如果支持则返回 true，否则返回 false
     */
    public function isSupportedRegion(string $regionCode): bool
    {
        return in_array(strtoupper($regionCode), $this->phoneUtil->getSupportedRegions());
    }

    /**
     * 获取电话号码的类型（如移动、固定电话等）
     *
     * @return int 电话号码类型的常量值
     */
    public function getNumberType(): int
    {
        return $this->phoneUtil->getNumberType($this->phoneNumber);
    }

    /**
     * 检查当前电话号码是否与给定的电话号码匹配
     *
     * @param string $otherNumber 要比较的电话号码
     * @param string|null $countryCode 国家代码（默认为 'US'）
     * @return bool 如果匹配则返回 true，否则返回 false
     */
    public function isMatch(string $otherNumber, ?string $countryCode = 'US'): bool
    {
        try {
            $otherPhoneNumber = $this->phoneUtil->parse($otherNumber, $countryCode);
            // 由于 EXACT_MATCH 常量不存在，我们将直接比较格式化后的号码
            return $this->format() === $this->phoneUtil->format($otherPhoneNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * 获取国家代码（如 1, 86 等）
     *
     * @return int 国家代码
     */
    public function getCountryCode(): int
    {
        return $this->phoneNumber->getCountryCode();
    }

    /**
     * 获取不包含国家代码的号码
     *
     * @return string 号码
     */
    public function getNationalNumber(): string
    {
        return $this->phoneNumber->getNationalNumber();
    }
}
