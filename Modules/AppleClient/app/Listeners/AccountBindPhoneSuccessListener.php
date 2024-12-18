<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
use App\Models\Phone;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;

class AccountBindPhoneSuccessListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AccountBindPhoneSuccessEvent $event): void
    {
        $model = $event->account->model();

        DB::transaction(function () use ($model, $event) {

            $model->update([
                'status'             => AccountStatus::BIND_SUCCESS,
                'bind_phone'         => $event->addSecurityVerifyPhone->getPhoneNumber(),
                'bind_phone_address' => $event->addSecurityVerifyPhone->getPhoneAddress(),
            ]);

            Phone::wherePhone($event->addSecurityVerifyPhone->getPhoneNumber())->update(
                ['status' => Phone::STATUS_BOUND]
            );

            $model->logs()->create(['action' => '添加授权号码', 'description' => $event->message]);
        });

    }
}
