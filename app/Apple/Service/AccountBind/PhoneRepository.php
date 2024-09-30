<?php

namespace app\Apple\Service\AccountBind;

use App\Models\Phone;
use Illuminate\Support\Facades\DB;

trait PhoneRepository
{
    abstract public function getUsedPhoneIds(): array;

    /**
     * @return Phone
     * @throws \Throwable
     */
    protected function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            $phone = Phone::query()
                ->where('status', Phone::STATUS_NORMAL)
                ->whereNotNull(['phone_address', 'phone'])
                ->whereNotIn('id', $this->getUsedPhoneIds())
                ->lockForUpdate()
                ->firstOrFail();

            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }
}
