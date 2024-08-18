<?php

namespace App\Events;

use App\Apple\Service\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Account $account,
        public AccountStatus $status,
        public ?string $action = null,
        public ?string $description = null
    ) {

    }
}
