<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use App\Apple\Enums\AccountStatus;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use Filament\Notifications\Actions\Action;
use Carbon\Carbon;
class SynchronousAppleIdSignInStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Account $appleid)
    {
        $this->onQueue('appleid_synchronous_sign_in_status');
    }

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
    public int $timeout = 60 * 5;

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
        return 60 * 5;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            //属性模型数据，防止数据删除，状态修改
            $this->appleid->refresh();

            if(!$this->appleid || $this->appleid->status === AccountStatus::BIND_SUCCESS){
                return;
            }

            
            // 获取账号管理员 token 同步登录状态
            $this->appleid->appleIdResource()
                ->getAccountManagerResource()
                ->token();


                
            // 其实还可以获取 awat cookie 值的过期时间来设置同步时间
            // 获取 awat cookie 值的过期时间
            // $awat = $this->appleid->cookieJar()?->getCookieByName('awat')->getExpires();

            // //将时间戳转换成时间
            // $awat = Carbon::createFromTimestamp($awat);

            // //获取过期时间
            // $awat = $awat->diffInMinutes();

            // self::dispatch($this->appleid)->delay(now()->addMinutes($awat));
            
            //在设置时间后重新同步登录状态
            self::dispatch($this->appleid)->delay(now()->addMinutes(10));

        } catch (\Throwable $e) {
            $this->appleid->status = AccountStatus::BIND_FAIL;
            $this->appleid->save();

            Log::error('同步苹果 ID 登录状态失败', ['account' => $this->appleid->appleid, 'error' => $e->getMessage()]);

           if(!$e instanceof UnauthorizedException){
                self::dispatch($this->appleid)->delay(now()->addMinutes(10));
           }

           Notification::make()
            ->title("同步苹果 ID 登录状态失败")
            ->body($e->getMessage())
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('查看账户')
                    ->button()
                    ->url(ViewAccount::getUrl(['record' => $this->appleid->id]), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase(User::first());
        }
    }
}
