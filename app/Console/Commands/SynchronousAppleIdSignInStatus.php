<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Apple\Enums\AccountStatus;
use App\Jobs\SynchronousAppleIdSignInStatusJob;
class SynchronousAppleIdSignInStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:synchronous-apple-id-sign-in-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步苹果ID登录状态';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Account::whereIn('status', [AccountStatus::AUTH_SUCCESS, AccountStatus::BIND_FAIL])
            ->chunk(100, 
                fn (Collection $appleids) => $appleids->map(
                    fn (Account $appleid) => SynchronousAppleIdSignInStatusJob::dispatch($appleid)
                )
            );
    }
}
