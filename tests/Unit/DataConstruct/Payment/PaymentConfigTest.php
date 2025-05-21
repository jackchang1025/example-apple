<?php

use Spatie\LaravelData\Exceptions\CannotCreateData;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Payment\Option;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Payment\OwnerName;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Payment\PaymentConfig;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Payment\PaymentMethodOption;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Payment\PhoneNumber;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Payment\PrimaryPaymentMethod;


it('can create PaymentConfig from payment json', function () {

    $json = file_get_contents(__DIR__.'/payment.json');

    $paymentConfig = PaymentConfig::from(json_decode($json, true, 512, JSON_THROW_ON_ERROR));

    expect($paymentConfig)->toBeInstanceOf(PaymentConfig::class)
        ->and($paymentConfig->paymentMethodOptions)->toHaveCount(4)
        ->and($paymentConfig->paymentMethodOptions[0])->toBeInstanceOf(PaymentMethodOption::class)
        ->and($paymentConfig->paymentMethodOptions[0]->option)->toBeInstanceOf(Option::class)
        ->and($paymentConfig->paymentMethodOptions[0]->option->name)->toBe('wechatpay')
        ->and($paymentConfig->paymentMethodOptions[0]->option->displayName)->toBe('WeChat Pay')
        ->and($paymentConfig->currentPaymentOption)->toBeInstanceOf(PaymentMethodOption::class)
        ->and($paymentConfig->currentPaymentOption->option->name)->toBe('wechatpay')
        ->and($paymentConfig->currentPaymentOption->option->displayName)->toBe('WeChat Pay')
        ->and($paymentConfig->paymentMethodUpdateAllowed)->toBeTrue()
        ->and($paymentConfig->itunesAccountSummaryUrl)->toBe(
            'itms://buy.itunes.apple.com/WebObjects/MZFinance.woa/wa/accountSummary'
        )
        ->and($paymentConfig->primaryPaymentMethod)->toBeInstanceOf(PrimaryPaymentMethod::class)
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber)->toBeInstanceOf(PhoneNumber::class)
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber->number)->toBe('xxxxxxxxxxx')
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber->numberWithoutAreaCode)->toBe('xxxxxxxxxxx')
        ->and($paymentConfig->primaryPaymentMethod->ownerName)->toBeInstanceOf(OwnerName::class)
        ->and($paymentConfig->primaryPaymentMethod->ownerName)->toBeInstanceOf(OwnerName::class)
        ->and($paymentConfig->primaryPaymentMethod->paymentMethodOption)->toBe(['name' => 'wechatpay'])
        ->and($paymentConfig->primaryPaymentMethod->weChatPay)->toBeTrue()
        ->and($paymentConfig->primaryPaymentMethod->partnerLogin)->toBe('xxxxxxxxxxx')
        ->and($paymentConfig->primaryPaymentMethod->partnerLoginTest)->toBeNull();
});

// 测试无效 JSON 的情况
it('throws exception for invalid JSON', function () {
    $invalidJson = '{invalid_json:}';
    PaymentConfig::from($invalidJson);
})->throws(CannotCreateData::class);

// 测试缺少必要字段的情况
it('throws exception when missing required fields', function () {
    $incompleteJson = '{"primaryPaymentMethod": {}}';
    PaymentConfig::from($incompleteJson);
})->throws(CannotCreateData::class);

