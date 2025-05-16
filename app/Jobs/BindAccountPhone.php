<?php

namespace App\Jobs;

use App\Services\AddSecurityVerifyPhoneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Apple\Enums\AccountStatus;
class BindAccountPhone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 作业可尝试的次数。
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * 作业在超时前可以运行的秒数。
     *
     * @var int
     */
    public int $timeout = 60 * 30;

    /**
     * 唯一任务锁
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->appleid->appleid;
    }

    /**
     * 指定一个超时时间，超过该时间任务不再保持唯一
     * @return int
     */
    public function uniqueFor(): int
    {
        return 3600; // 1 小时，单位为秒
    }

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Account $appleid)
    {
        $this->onQueue('appleid_add_security_verify_phone');
    }


    public function handle(): void
    {
        try {

            $this->appleid->refresh();

            if(!$this->appleid || $this->appleid->status === AccountStatus::BIND_SUCCESS){
                return;
            }

            (new AddSecurityVerifyPhoneService($this->appleid))->handle();

        } catch (\Throwable $e) {

            Log::error("BindAccountPhone job failed {$this->appleid->appleid} {$e}");

            $this->fail($e);

            //如果绑定失败，则重新同步登录状态，保持账号不退出
            SynchronousAppleIdSignInStatusJob::dispatch($this->appleid)->delay(now()->addMinutes(10));

            //如果绑定失败，则在设置时间后重新绑定
            self::dispatch($this->appleid)->delay(delay: now()->addMinutes(value: 60));
        }
    }
}
