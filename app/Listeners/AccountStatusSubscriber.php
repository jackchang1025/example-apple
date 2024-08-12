<?php

namespace App\Listeners;

use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Events\AccountLoginFailEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Events\AccountStatusChanged;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class AccountStatusSubscriber
{
    public function handleStatusChange(AccountStatusChanged $event): void
    {
        $event->account->update(['status' => $event->status]);
    }

    public function handleLoginSuccess(AccountLoginSuccessEvent $event): void
    {
        $this->handleStatusChange($event);
        // 可以添加登录成功特有的逻辑
        Log::info("Account {$event->account->account} logged in successfully");
    }

    public function handleLoginFail(AccountLoginFailEvent $event): void
    {
        $this->handleStatusChange($event);
        // 可以添加登录成功特有的逻辑
        Log::info("Account {$event->account->account} logged in failed");
    }

    public function handleAuthSuccess(AccountAuthSuccessEvent $event): void
    {
        $this->handleStatusChange($event);
        // 可以添加登录成功特有的逻辑
        Log::info("Account {$event->account->account} auth in successfully");
    }

    public function handleAuthFail(AccountAuthFailEvent $event): void
    {
        $this->handleStatusChange($event);
        // 可以添加登录成功特有的逻辑
        Log::info("Account {$event->account->account} auth in failed");
    }


    public function handleBindFail(AccountBindPhoneFailEvent $event): void
    {
        $this->handleStatusChange($event);
        // 可以添加绑定失败特有的逻辑
        Log::warning("Account {$event->account->account} failed to bind");
    }

    public function handleBindSuccess(AccountBindPhoneSuccessEvent $event): void
    {
        $this->handleStatusChange($event);
        // 可以添加绑定失败特有的逻辑
        Log::warning("Account {$event->account->account} successfully to bind");
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            AccountStatusChanged::class => 'handleStatusChange',

            AccountLoginSuccessEvent::class => 'handleLoginSuccess',
            AccountLoginFailEvent::class => 'handleLoginFail',

            AccountAuthSuccessEvent::class => 'handleAuthSuccess',
            AccountAuthFailEvent::class => 'handleAuthFail',

            AccountBindPhoneFailEvent::class => 'handleBindFail',
            AccountBindPhoneSuccessEvent::class => 'handleBindSuccess',
        ];
    }
}
