<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Weijiajia\SaloonphpAppleClient\DataConstruct\PhoneNumber;
use App\Services\AddSecurityVerifyPhoneService;

class SignIn extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple-id:sign-in
                            {appleId? : 苹果账号}
                            {password? : 密码}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '登录苹果账号';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appleId = $this->argument('appleId');
        if(!$appleId){
            $appleId = $this->ask('请输入账号');
        }
        $password = $this->argument('password');
        if(!$password){
            $password = $this->secret('请输入密码');
        }

        $apple = Account::updateOrCreate(
            ['appleid' => $appleId], // 用于查找账户的条件
            ['password' => $password]  // 需要更新或创建的值（密码已哈希）
        );

        $apple->config()->add('apple_auth_url',value: config('apple.apple_auth_url'));
        $apple->withDebug(true);

        //todo 添加登陆状态失败
        $aidsp = $apple->cookieJar()?->getCookieByName('aidsp');

        if(!$aidsp){
            $apple->appleIdResource()->signIn();

            $auth = $apple->appleIdResource()->appleAuth();

            if ($auth->hasTrustedDevices() || $auth->getTrustedPhoneNumbers()->count() === 0) {

                $code = $this->ask('请输入双重认证');

                $apple->appleIdResource()->verifySecurityCode($code);

            }else if ($auth->getTrustedPhoneNumbers()->count() >= 2) {

                $choices = $auth->getTrustedPhoneNumbers()
                    ->toCollection()
                    ->map(function (PhoneNumber $phone) {
                        return "{$phone->numberWithDialCode}";
                    })
                    ->toArray();

                // 选择一个授权手机号
                $phone = $this->choice(
                    '请选择一个授权手机号',
                    $choices,
                );

                $phone = $auth->getTrustedPhoneNumbers()->toCollection()->firstWhere('numberWithDialCode', $phone);

                $apple->appleIdResource()->sendPhoneSecurityCode($phone->id);

                $code = $this->ask('请输入手机验证码');

                $apple->appleIdResource()->verifyPhoneVerificationCode($phone->id, $code);

            }
        }



        $addSecurityVerifyPhoneService = new AddSecurityVerifyPhoneService($apple);

        $addSecurityVerifyPhoneService->handle();

    }
}
