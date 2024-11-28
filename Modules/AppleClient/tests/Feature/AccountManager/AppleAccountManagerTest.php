<?php


use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Database\Factories\SendDeviceSecurityCodeFactory;
use Modules\AppleClient\Database\Factories\VerifyPhoneSecurityCodeFactoryFactory;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\DataConstruct\Auth\Auth;
use Modules\AppleClient\Service\DataConstruct\NullData;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\PhoneNumberVerification;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\DataConstruct\Sign\Sign;
use Modules\AppleClient\Service\DataConstruct\ValidatePassword\ValidatePassword;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\AppleAuth\Request\Complete;
use Modules\AppleClient\Service\Integrations\AppleAuth\Request\Init;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhoneRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhoneSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\TokenRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AuthenticatePasswordRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendTrustedDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SigninInit;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyTrustedDeviceSecurityCode;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Spatie\LaravelData\DataCollection;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {

    $this->account = Account::factory()->create([
        'account'            => 'jackchang2021@163.com',
        'password'           => 'AtA3FH2sBfrtSv6',
        'bind_phone'         => '+85297403063',
        'bind_phone_address' => 'http://gsm888.vip/api/sms/recordText?key=1b484c5543854373a51d972823f7dfed',
        'type'               => 'imported',
    ]);

    \App\Models\ProxyConfiguration::factory()->create([
        'name'              => 'test',
        'configuration'     => [
            'huashengdaili'  => [
                'mode'    => 'api',
                'session' => time(),
            ],
            'default_driver' => 'huashengdaili',
        ],
        'is_active'         => 1,
        'ipaddress_enabled' => 0,
        'proxy_enabled'     => 0,
    ]);


    $this->appleClientFactory = app(AppleAccountManagerFactory::class);
    $this->accountManager     = $this->appleClientFactory->create($this->account);

    // 设置特定的模拟响应
    MockClient::global([
        Init::class => MockResponse::make(
            body: [
                'value' => fake()->title(),
                'key'   => fake()->text(),
            ],
            status: 200
        ),

        SigninInit::class => MockResponse::make(
            body: [
                'salt'      => fake()->title(),
                'b'         => fake()->text(),
                'c'         => fake()->text(),
                'iteration' => fake()->text(),
                'protocol'  => fake()->text(),
            ],
            status: 200
        ),

        Complete::class => MockResponse::make(
            body: [
                'M1' => fake()->title(),
                'M2' => fake()->text(),
                'c'  => fake()->text(),
            ],
            status: 200
        ),

        SignInComplete::class => MockResponse::make(
            body: [
                'authType' => fake()->title(),
            ],
            status: 409
        ),

        \Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\Auth::class => MockResponse::make(
            body: '<script type="application/json" class="boot_args">
    {"direct":{"scriptSk7Url":"https://appleid.cdn-apple.com/appleauth/static/module-assets/home-3d9cc87dfa00944927b0.js","scriptUrl":"https://appleid.cdn-apple.com/appleauth/static/jsj/N1862500467/widget/auth/hsa2.js","module":"widget/auth/components/hsa2/hsa2","isReact":true,"authUserType":"hsa2","hasTrustedDevices":false,"twoSV":{"supportedPushModes":["voice","sms"],"phoneNumberVerification":{"trustedPhoneNumbers":[{"numberWithDialCode":"+852 •••• ••63","pushMode":"sms","obfuscatedNumber":"•••• ••63","lastTwoDigits":"63","id":4},{"numberWithDialCode":"+86 ••• •••• ••24","pushMode":"sms","obfuscatedNumber":"••• •••• ••24","lastTwoDigits":"24","id":2},{"numberWithDialCode":"+852 •••• ••08","pushMode":"sms","obfuscatedNumber":"•••• ••08","lastTwoDigits":"08","id":6},{"numberWithDialCode":"+852 •••• ••93","pushMode":"sms","obfuscatedNumber":"•••• ••93","lastTwoDigits":"93","id":5},{"numberWithDialCode":"+852 •••• ••35","pushMode":"sms","obfuscatedNumber":"•••• ••35","lastTwoDigits":"35","id":3}],"securityCode":{"length":6,"tooManyCodesSent":false,"tooManyCodesValidated":false,"securityCodeLocked":false,"securityCodeCooldown":false},"authenticationType":"hsa2","recoveryUrl":"https://iforgot.apple.com/phone/add?prs_account_nm=jackchang2021%40163.com\u0026autoSubmitAccount=true\u0026appId=142","cantUsePhoneNumberUrl":"https://iforgot.apple.com/iforgot/phone/add?context=cantuse\u0026prs_account_nm=jackchang2021%40163.com\u0026autoSubmitAccount=true\u0026appId=142","recoveryWebUrl":"https://iforgot.apple.com/password/verify/appleid?prs_account_nm=jackchang2021%40163.com\u0026autoSubmitAccount=true\u0026appId=142","repairPhoneNumberUrl":"https://gsa.apple.com/appleid/account/manage/repair/verify/phone","repairPhoneNumberWebUrl":"https://appleid.apple.com/widget/account/repair?#!repair","noTrustedDevices":true,"aboutTwoFactorAuthenticationUrl":"https://support.apple.com/kb/HT204921","autoVerified":false,"showAutoVerificationUI":false,"supportsCustodianRecovery":false,"hideSendSMSCodeOption":false,"supervisedChangePasswordFlow":false,"trustedPhoneNumber":{"numberWithDialCode":"+852 •••• ••63","pushMode":"sms","obfuscatedNumber":"•••• ••63","lastTwoDigits":"63","id":4},"hsa2Account":true,"restrictedAccount":false,"supportsRecovery":true,"managedAccount":false},"authFactors":["robocall","sms","generatedcode"],"source_returnurl":"https://idmsa.apple.com/","sourceAppId":93},"referrerQuery":"","urlContext":"/appleauth","tag":"\u003Chsa2 class=\u0027auth-v1\u0027 suppress-iforgot=\"{suppressIforgot}\" skip-trust-browser-step=\"{skipTrustBrowserStep}\"\u003E\u003C/hsa2\u003E","authType":"hsa2","authInitialRoute":"auth/verify/phone/options","appleIDUrl":"https://appleid.apple.com"},"additional":{"canRoute2sv":true}}
</script>',
        ),

        SendPhoneSecurityCode::class => MockResponse::make(
            body: (new \Modules\AppleClient\Database\Factories\SendPhoneVerificationCodeFactory())->makeOne()->toArray(
            ),
        ),

        VerifyTrustedDeviceSecurityCode::class => MockResponse::make(
            body: [],
        ),

        VerifyPhoneSecurityCode::class       => MockResponse::make(
            body: (new VerifyPhoneSecurityCodeFactoryFactory())->makeOne()->toArray(),
        ),
        SendTrustedDeviceSecurityCode::class => MockResponse::make(
            body: (new SendDeviceSecurityCodeFactory())->makeOne()->toArray(),
        ),

        TokenRequest::class => MockResponse::make(
            body: [],
        ),

        AuthenticatePasswordRequest::class => MockResponse::make(
            body: [],
        ),//AuthenticatePassword

        SecurityVerifyPhoneRequest::class => MockResponse::make(
            body: (new \Modules\AppleClient\Database\Factories\SecurityVerifyPhoneFactory())->makeOne()->toArray(),
        ),

        SecurityVerifyPhoneSecurityCodeRequest::class => MockResponse::make(
            body: (new \Modules\AppleClient\Database\Factories\SecurityVerifyPhoneFactory())->makePhoneNumber(
            )->toArray(),
        ),
    ]);

});

it('can create account manager', function () {

    expect($this->accountManager)->toBeInstanceOf(\Modules\AppleClient\Service\AppleAccountManager::class);
});


it('can sign and cache sign data', function () {

    // 首次调用sign()方法
    $signData = $this->accountManager->sign();

    // 验证返回的SignData
    expect($signData)->toBeInstanceOf(Sign::class)
        ->and($signData->authType)->not->toBeNull()
        ->and($signData->expiresAt)->not->toBeNull()
        ->and($signData->isValid())->toBeTrue();

    // 再次调用sign()方法,应该返回缓存的数据而不是重新调用API
    $cachedSignData = $this->accountManager->sign();

    // 验证返回的是相同的SignData实例
    expect($cachedSignData)->toBe($signData);

});

it('refreshes sign data when expired', function () {

    // 创建一个过期的SignData
    $expiredSignData = Sign::from([
        'authType'  => 'expired',
        'expiresAt' => now()->subMinutes(5), // 5分钟前过期
    ]);

    // 设置过期的SignData
    $this->accountManager->withSignData($expiredSignData);

    // 调用sign()方法,应该刷新数据
    $newSignData = $this->accountManager->sign();

    // 验证返回的是新的SignData
    expect($newSignData)->toBeInstanceOf(Sign::class)
        ->and($newSignData)->not->toBe($expiredSignData)
        ->and($newSignData->isValid())->toBeTrue()
        ->and($expiredSignData->isValid())->toBeFalse();
});

it('can authenticate and cache auth data', function () {

    // 首次调用auth()方法
    $authData = $this->accountManager->auth();

    // 再次调用auth()方法,应该返回缓存的数据而不是重新调用API
    $cachedAuthData = $this->accountManager->auth();

    // 验证返回的是相同的AuthData实例
    expect($cachedAuthData)->toBe($authData)
        ->and($authData)->toBeInstanceOf(Auth::class);

});

it('can refresh auth data', function () {

    // 首次调用auth()方法
    $initialAuthData = $this->accountManager->auth();

    // 调用refreshAuth()方法
    $refreshedAuthData = $this->accountManager->refreshAuth();

    // 验证refreshed的AuthData
    expect($refreshedAuthData)->toBeInstanceOf(Auth::class)
        ->and($refreshedAuthData)->not->toBe($initialAuthData)
        ->and($refreshedAuthData->getTrustedPhoneNumbers())->toBeInstanceOf(DataCollection::class)
        ->and($refreshedAuthData->getTrustedPhoneNumber())->toBeInstanceOf(PhoneNumber::class)
        ->and($refreshedAuthData->hasTrustedDevices())->toBeFalse();
});

it('can verify security code', function () {

    // 首次调用auth()方法
    $NullData = $this->accountManager->verifySecurityCode(fake()->randomNumber(6));

    // 验证refreshed的AuthData
    expect($NullData)->toBeInstanceOf(NullData::class)
        ->and($NullData->updateAt)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('can verify sms security code', function () {

    // 首次调用auth()方法
    $VerifyPhoneSecurityCode = $this->accountManager
        ->verifyPhoneCode(fake()->randomNumber(), fake()->randomNumber(6));

    // 验证refreshed的AuthData
    expect($VerifyPhoneSecurityCode)->toBeInstanceOf(
        \Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode::class
    );
});

it('can verify sendSecurityCode', function () {

    // 首次调用auth()方法
    $sendSecurityCode = $this->accountManager
        ->sendSecurityCode();

    // 验证refreshed的AuthData
    expect($sendSecurityCode)->toBeInstanceOf(SendDeviceSecurityCode::class);
});

it('can verify sendPhoneSecurityCode', function () {

    // 首次调用auth()方法
    $sendPhoneSecurityCode = $this->accountManager
        ->sendPhoneSecurityCode(fake()->randomNumber(1));

    // 验证refreshed的AuthData
    expect($sendPhoneSecurityCode)->toBeInstanceOf(SendPhoneVerificationCode::class);
});

it('can verify token', function () {

    // 首次调用auth()方法
    $getToken = $this->accountManager
        ->getToken();

    // 验证refreshed的AuthData
    expect($getToken)->toBeInstanceOf(\Modules\AppleClient\Service\DataConstruct\Token\Token::class);
});

it('can verify securityVerifyPhoneSecurityCode', function () {

    $phone = \App\Models\Phone::factory()->create([
        'status' => 'invalid',
    ]);

    // 首次调用auth()方法
    $securityVerifyPhoneSecurityCode = $this->accountManager
        ->securityVerifyPhoneSecurityCode(
            id: $phone->id,
            phoneNumber: $phone->national_number,
            countryCode: $phone->country_code,
            countryDialCode: $phone->country_dial_code,
            code: fake()->randomNumber(6)
        );

    // 验证refreshed的AuthData
    expect($securityVerifyPhoneSecurityCode)->toBeInstanceOf(
        \Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone::class
    )
        ->and($securityVerifyPhoneSecurityCode->phoneNumber)->toBeInstanceOf(
            \Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\PhoneNumber::class
        )
        ->and($securityVerifyPhoneSecurityCode->phoneNumberVerification)->toBeNull();
});

it('can verify securityVerifyPhoneSecurityCode VerificationCodeException', function () {

    $phone = \App\Models\Phone::factory()->create();

    MockClient::global()->addResponse(
        MockResponse::make(
            status: 400
        ),
        SecurityVerifyPhoneSecurityCodeRequest::class
    );

    // 首次调用auth()方法
    $securityVerifyPhoneSecurityCode = $this->accountManager
        ->securityVerifyPhoneSecurityCode(
            id: $phone->id,
            phoneNumber: $phone->national_number,
            countryCode: $phone->country_code,
            countryDialCode: $phone->country_dial_code,
            code: fake()->randomNumber(6)
        );

})->throws(VerificationCodeException::class);

it('can verify ValidatePassword', function () {

    // 首次调用auth()方法
    $getValidatePassword = $this->accountManager
        ->getValidatePassword();

    // 验证refreshed的AuthData
    expect($getValidatePassword)->toBeInstanceOf(ValidatePassword::class);
});

it('can securityVerifyPhone', function () {

    $phone = \App\Models\Phone::factory()->create();

    $securityVerifyPhone = $this->accountManager->securityVerifyPhone(
        countryCode: $phone->country_code,
        phoneNumber: $phone->national_number,
        countryDialCode: $phone->country_dial_code
    );

    expect($securityVerifyPhone)->toBeInstanceOf(
        \Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone::class
    )
        ->and($securityVerifyPhone->phoneNumberVerification)->toBeInstanceOf(PhoneNumberVerification::class)
        ->and($securityVerifyPhone->phoneNumber)->toBeNull();
});

it('can send verifyPhoneCodeAndValidateStolenDeviceProtection ModelNotFoundException', function () {

    $phone = \App\Models\Phone::factory()->create([
        'status' => 'invalid',
    ]);

    $securityVerifyPhone = $this->accountManager->verifyPhoneCodeAndValidateStolenDeviceProtection(
        id: $phone->id,
        code: $phone->national_number,
    );

})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('can send verifyPhoneCodeAndValidateStolenDeviceProtection', function () {

    // 设置特定的模拟响应
    MockClient::global()->addResponse(
        MockResponse::make(
            body: (new \Modules\AppleClient\Database\Factories\SecurityVerifyPhoneFactory())->makeOne()->toArray(),
            status: 467
        ),
        SecurityVerifyPhoneRequest::class
    );

    $phone = \App\Models\Phone::factory()->create([
        'status' => 'normal',
    ]);

    $securityVerifyPhone = $this->accountManager->verifyPhoneCodeAndValidateStolenDeviceProtection(
        id: $phone->id,
        code: $phone->national_number,
    );

})->throws(
    StolenDeviceProtectionException::class,
    '已开启“失窃设备保护”，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备'
);
//
