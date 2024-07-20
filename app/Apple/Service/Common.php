<?php

namespace App\Apple\Service;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class Common
{

    /**
     * 从字符串中提取 6 位数字
     *
     * @param string $input 输入字符串
     * @return string|null 提取的 6 位数字，如果没有找到则返回 null
     */
    public static function extractSixDigitNumber(string $input): ?string
    {
        if (preg_match('/\b\d{6}\b/', $input, $matches)) {
            return $matches[0];
        }

        return null;
    }

    public function extractInfo($string)
    {
        $result = [
            'code' => null,
            'first_date' => null,
            'second_date' => null,
        ];

        // 提取 code
        if (preg_match('/code:(\d+)/', $string, $matches)) {
            $result['code'] = $matches[1];
        } elseif (preg_match('/#(\d+)/', $string, $matches)) {
            $result['code'] = $matches[1];
        }

        // 提取日期
        if (preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $string, $matches)) {
            if (isset($matches[1][0])) {
                $result['first_date'] = $matches[1][0];
            }
            if (isset($matches[1][1])) {
                $result['second_date'] = $matches[1][1];
            }
        }

        return $result;
    }




    public static function parsePhone(string $phoneNumber, string $countryCode = 'US'):?string
    {
        // 创建 PhoneNumberUtil 实例
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // 解析电话号码
            $numberProto = $phoneUtil->parse($phoneNumber, $countryCode);

            // 获取国家 calling code（前缀）
            return $numberProto->getCountryCode();

        } catch (NumberParseException $e) {

            return null;
        }
    }

    public static function isValidNumber(string $phoneNumber, string $countryCode = 'US'):bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumber = $phoneUtil->parse($phoneNumber, $countryCode);

            return $phoneUtil->isValidNumber($phoneNumber);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function  formatPhone(string $phoneNumber, string $countryCode = 'US'): ?string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumber = $phoneUtil->parse($phoneNumber, $countryCode);
            return $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

        } catch (\Exception $e) {
            // Log the error or handle it as needed
            return null;
        }
    }
}
