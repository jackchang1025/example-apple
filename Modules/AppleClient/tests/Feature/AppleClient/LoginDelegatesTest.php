<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\Exception\AppleRequestException\LoginRequestException;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

uses(TestCase::class);

beforeEach(function () {

    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    $this->appleClient = new AppleClient();
});

it('test getLoginDelegates', function () {

    expect($this->appleClient->getLoginDelegates())->toBeNull();
});

it('test loginDelegates exception', function () {

    expect($this->appleClient->loginDelegates());

})->throws(
    LoginRequestException::class,
    'This Apple ID has been locked for security reasons. Visit iForgot to reset your account (https://iforgot.apple.com).'
);

it('login delegates', function () {

    // CI 环境跳过交互测试
    if (env('CI')) {
        $this->markTestSkipped('在 CI 环境跳过交互测试');
    }

    Coroutine::create(function () {
        try {

            $this->appleId     = 'jackchang2021@163.com';
            $this->password    = 'AtA3FH2sBfrtSv6';
            $this->appleClient = new AppleClient(new Account($this->appleId, $this->password));
            expect($this->appleClient->loginDelegates())->toBeInstanceOf(
                \Modules\AppleClient\Service\Response\Response::class
            );


            $channel = new Channel(1);

            //子协程等待输入验证码
            Coroutine::create(static function () use (&$channel) {


                try {
                    echo "请输入验证码:\n";
                    $authCode = trim(fgets(STDIN));

                    $sanitized = preg_replace('/[^0-9]/', '', $authCode);

                    if (strlen($sanitized) !== 6) {
                        throw new InvalidArgumentException('Authentication code must be 6 digits');
                    }

                    // 推送验证码到通道
                    $channel->push($sanitized);
                } catch (Throwable $e) {
                    // 错误处理
                    $channel->push(null);
                    echo "验证码错误: ".$e->getMessage()."\n";
                }

            });

            //主进程等待输入验证码
            $authCode = $channel->pop();

            $loginDelegates = $this->appleClient->authDelegates($authCode);
            expect($loginDelegates)->toBeInstanceOf(LoginDelegates::class)->dump();

            $FamilyDetails = $this->appleClient->getFamilyDetails();
            expect($FamilyDetails)->toBeInstanceOf(FamilyDetails::class)->dump();

        } catch (Throwable $e) {
            throw $e;
        }
    });

});


