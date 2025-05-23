<?php

namespace App\Jobs;

use App\Services\AddSecurityVerifyPhoneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;
use App\Apple\Enums\AccountStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Saloon\Exceptions\SaloonException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

class BindAccountPhone implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * 作业在超时前可以运行的秒数。
     * 单次尝试的超时时间。
     * @var int
     */
    public int $timeout = 60 * 10; // 30 minutes

    /**
     * 唯一任务锁
     * @return string
     */
    public function uniqueId(): string
    {
        return "bind_account_phone_lock_{$this->appleid->appleid}";
    }

    /**
     * 指定一个超时时间，超过该时间任务不再保持唯一
     * ShouldBeUnique 锁的生命周期。
     * uniqueFor >= (作业可尝试的次数 * 单次尝试的超时时间) + ((作业可尝试的次数 - 1) * 每次重试的延迟时间)
     * @return int
     */
    public function uniqueFor(): int
    {
        return 60 * 10;
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
            if ($this->appleid->status === AccountStatus::BIND_SUCCESS) {
                return;
            }

            if ($this->appleid->status === AccountStatus::BIND_ING) {
                return;
            }

            (new AddSecurityVerifyPhoneService($this->appleid))->handle();

            Log::info("[BindAccountPhone] Successfully bound phone for account {$this->appleid->appleid} on attempt {$this->attempts()}.");
        } catch (ModelNotFoundException $e) {

            return;
        } catch (UnauthorizedException $e) {

            return;
        } catch (SaloonException $e) {

            SynchronousAppleIdSignInStatusJob::dispatch($this->appleid)->delay(now()->addMinutes(10));
            AppleidAddSecurityVerifyPhone::dispatch($this->appleid)->delay(now()->addMinutes(60));

            return;
        } catch (\Throwable $e) {

            Log::error("{$e}");
            return;
        }
    }
}
