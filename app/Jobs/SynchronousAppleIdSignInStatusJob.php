<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;
use App\Apple\Enums\AccountStatus;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use App\Models\User;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use Filament\Notifications\Actions\Action;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Saloon\Exceptions\SaloonException;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use DateTime;
class SynchronousAppleIdSignInStatusJob implements ShouldQueue,ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Account $appleid)
    {
        $this->onQueue('appleid_synchronous_sign_in_status');
    }

    public function retryUntil(): DateTime
    {
        return now()->addHours(24);
    }

    /**
     * 作业在超时前可以运行的秒数。
     *
     * @var int
     */
    public int $timeout = 60 * 5;

    /**
     * 唯一任务锁
     * @return string
     */
    public function uniqueId(): string
    {
        return "appleid_synchronous_sign_in_status_lock_{$this->appleid->appleid}";
    }

    /**
     * 指定一个超时时间，超过该时间任务不再保持唯一
     * @return int
     */
    public function uniqueFor(): int
    {
        return 60 * 60 * 24;
    }

    /**
     * 定义每次重试之间的延迟（秒）。
     * @return int|array
     */
    public function backoff(): int|array
    {
        return 60 * 10;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $this->appleid->refresh();
            if ($this->appleid->status === AccountStatus::BIND_SUCCESS) {
                return;
            }
            
            if ($this->appleid->status === AccountStatus::BIND_ING) {
                $this->fail();
                return;
            }

            $this->appleid->appleIdResource()
                ->getAccountManagerResource()
                ->token();

            $this->fail();

        } catch (\Throwable $e) {

            if($e instanceof ModelNotFoundException){
                return;
            }

            $this->appleid->logs()
                ->create([
                    'action' => '同步苹果 ID 登录状态失败',
                    'request' => ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
                ]);

            Notification::make()
                ->title("同步苹果 ID 登录状态失败")
                ->body($e->getMessage())
                ->warning()
                ->actions([
                    Action::make('view')
                    ->label('查看账户')
                    ->button()
                    ->url(ViewAccount::getUrl(['record' => $this->appleid->id ?? null]), shouldOpenInNewTab: true)
                ])
                ->sendToDatabase(User::first());

            if($e instanceof SaloonException && !$e instanceof UnauthorizedException){
                //重新抛出异常让任务重试
                throw $e;
            }
        }
    }
}
