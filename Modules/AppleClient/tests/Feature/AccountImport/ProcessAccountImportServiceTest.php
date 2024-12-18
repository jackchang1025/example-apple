<?php

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleFactory;
use Modules\AppleClient\Service\ProcessAccountImportService;

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

//    $this->account            = Account::from([
//        'account'=>'16609531778',
//        'password'=>'Dabao5201314.',
//        'bind_phone'=>'+85256608441',
//        'bind_phone_address'=>'http://gsm888.vip/api/sms/recordText?key=7c3eaa206ada4c319a3bdd80546c2806',
//        'type'=>'imported',
//    ]);

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

    $this->appleClientFactory = app(AppleFactory::class);
    $this->accountManager     = $this->appleClientFactory->create($this->account);

    $this->processAccountImportService = new ProcessAccountImportService($this->accountManager);

});


it('can process account import', function () {

//    dd($this->accountManager);
    $this->processAccountImportService->handle();


    expect($this->account->devices())->not->toBeEmpty()->dump()
        ->and($this->account->status)->toBe(\App\Apple\Enums\AccountStatus::AUTH_SUCCESS);
});
