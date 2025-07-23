<?php

namespace App\Events\PhoneBinding;

use App\Models\Account;
use App\Models\Phone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 手机号绑定成功事件
 */
class PhoneBindingSucceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Account $account,
        public readonly Phone $phone,
        public readonly int $attempt = 1
    ) {}
}
