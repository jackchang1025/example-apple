<?php


use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\DataConstruct\Payment\Option;
use Modules\AppleClient\Service\DataConstruct\Payment\OwnerName;
use Modules\AppleClient\Service\DataConstruct\Payment\PaymentConfig;
use Modules\AppleClient\Service\DataConstruct\Payment\PaymentMethodOption;
use Modules\AppleClient\Service\DataConstruct\Payment\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\Payment\PrimaryPaymentMethod;
use Spatie\LaravelData\Exceptions\CannotCreateData;

uses(TestCase::class);

beforeEach(function () {


});

it('can create PaymentConfig from payment json', function () {

    $json = file_get_contents(__DIR__.'/payment.json');

    $paymentConfig = PaymentConfig::fromJson($json);


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
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber->number)->toBe('13777578474')
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber->numberWithoutAreaCode)->toBe('13777578474')
        ->and($paymentConfig->primaryPaymentMethod->ownerName)->toBeInstanceOf(OwnerName::class)
        ->and($paymentConfig->primaryPaymentMethod->ownerName)->toBeInstanceOf(OwnerName::class)
        ->and($paymentConfig->primaryPaymentMethod->paymentMethodOption)->toBe(['name' => 'wechatpay'])
        ->and($paymentConfig->primaryPaymentMethod->weChatPay)->toBeTrue()
        ->and($paymentConfig->primaryPaymentMethod->partnerLogin)->toBe('B*******34')
        ->and($paymentConfig->primaryPaymentMethod->partnerLoginTest)->toBeNull();
});

it('can create PaymentConfig from payment2 json', function () {

    $json = file_get_contents(__DIR__.'/payment2.json');

    $paymentConfig = PaymentConfig::fromJson($json);

    expect($paymentConfig)->toBeInstanceOf(PaymentConfig::class)
        ->and($paymentConfig->paymentMethodOptions)->toHaveCount(4)
        ->and($paymentConfig->paymentMethodOptions[0])->toBeInstanceOf(PaymentMethodOption::class)
        ->and($paymentConfig->paymentMethodOptions[0]->option)->toBeInstanceOf(Option::class)
        ->and($paymentConfig->paymentMethodOptions[0]->option->name)->toBe('none')
        ->and($paymentConfig->paymentMethodOptions[0]->option->displayName)->toBe('无')
        ->and($paymentConfig->currentPaymentOption)->toBeInstanceOf(PaymentMethodOption::class)
        ->and($paymentConfig->currentPaymentOption->option->name)->toBe('none')
        ->and($paymentConfig->currentPaymentOption->option->displayName)->toBe('无')
        ->and($paymentConfig->paymentMethodUpdateAllowed)->toBeTrue()
        ->and($paymentConfig->itunesAccountSummaryUrl)->toBe(
            'itms://buy.itunes.apple.com/WebObjects/MZFinance.woa/wa/accountSummary'
        )
        ->and($paymentConfig->primaryPaymentMethod)->toBeInstanceOf(PrimaryPaymentMethod::class)
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber)->toBeInstanceOf(PhoneNumber::class)
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber->number)->toBeNull()
        ->and($paymentConfig->primaryPaymentMethod->phoneNumber->numberWithoutAreaCode)->toBeNull()
        ->and($paymentConfig->primaryPaymentMethod->ownerName)->toBeInstanceOf(OwnerName::class)
        ->and($paymentConfig->primaryPaymentMethod->ownerName)->toBeInstanceOf(OwnerName::class)
        ->and($paymentConfig->primaryPaymentMethod->paymentMethodOption)->toBe(['name' => 'none'])
        ->and($paymentConfig->primaryPaymentMethod->weChatPay)->toBeFalse()
        ->and($paymentConfig->primaryPaymentMethod->partnerLogin)->toBeNull()
        ->and($paymentConfig->primaryPaymentMethod->partnerLoginTest)->toBeNull();
});

// 测试无效 JSON 的情况
it('throws exception for invalid JSON', function () {
    $invalidJson = '{invalid_json:}';
    PaymentConfig::fromJson($invalidJson);
})->throws(JsonException::class);

// 测试缺少必要字段的情况
it('throws exception when missing required fields', function () {
    $incompleteJson = '{"primaryPaymentMethod": {}}';
    PaymentConfig::fromJson($incompleteJson);
})->throws(CannotCreateData::class);

