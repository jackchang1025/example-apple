<?php

namespace App\Events\PhoneBinding;

use App\Models\Account;
use App\Models\Phone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * 手机号绑定失败事件
 */
class PhoneBindingFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Account $account,
        public readonly Throwable $exception,
        public readonly int $attempt = 1,
        public readonly ?Phone $phone = null
    ) {}
}
