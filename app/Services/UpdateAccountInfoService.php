<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;
use App\Models\AccountManager;
use App\Models\Devices;
use App\Models\Payment;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Device\Device;
use App\Events\UpdateAccountInfoSuccessed;
use App\Events\UpdateAccountInfoFailed;
use Illuminate\Support\Facades\Log;

class UpdateAccountInfoService
{
    public function __construct(protected readonly Account $appleId,protected readonly AuthenticationService $authService,)
    {
    }

    public function handle(): void
    {

        $this->appleId->refresh();

        //刷新账号，避免中途被删除
        if(!$this->appleId){
            return;
        }

        $this->authService->ensureAuthenticated($this->appleId);

        if (!$this->appleId->payment) {
            $this->updateOrCreatePaymentConfig();
        }

        if ($this->appleId->devices->isEmpty()) {
            $this->updateOrCreateDevices();
        }

        if (!$this->appleId->accountManager) {
            $this->updateOrCreateAccountManager();
        }

        $this->appleId->syncCookies();

    }

    protected function updateOrCreateAccountManager(): void
    {
        try {
            $accountManager = $this->appleId->appleIdResource()->getAccountManagerResource()->account();

            if ($accountManager?->account) {
                AccountManager::updateOrCreate(
                    ['account_id' => $this->appleId->id],
                    $accountManager->toArray()
                );

                event(new UpdateAccountInfoSuccessed($this->appleId,'账号信息'));
            }

        } catch (Throwable $e) {

            event(new UpdateAccountInfoFailed($this->appleId,$e,'账号信息'));
        }
    }

    public function updateOrCreatePaymentConfig(): void
    {
        try {

            $primaryPaymentMethod = $this->appleId->appleIdResource()
            ->getPaymentResource()
            ->getPayment()
            ->primaryPaymentMethod;

            Payment::updateOrCreate(
                [
                    'account_id' => $this->appleId->id,
                    'payment_id' => $primaryPaymentMethod->paymentId,
                ],
                $primaryPaymentMethod->toArray()
            );

            event(new UpdateAccountInfoSuccessed($this->appleId,'支付信息'));

        } catch (Throwable $e) {
            
            event(new UpdateAccountInfoFailed($this->appleId,$e,'支付信息'));
        }
    }

    /**
     * @return Collection
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function updateOrCreateDevices(): void
    {
        try {

            $this->appleId->appleIdResource()
            ->getDevicesResource()
            ->getDevicesDetails()
            ->toCollection()
            ->map(fn (Device $device) => Devices::updateOrCreate(
                [
                    'account_id' => $this->appleId->id,
                    'device_id'  => $device->deviceId,
                ],
                $device->toArray()
            ));

            event(new UpdateAccountInfoSuccessed($this->appleId,'设备信息'));

        } catch (Throwable $e) {
            event(new UpdateAccountInfoFailed($this->appleId,$e,'设备信息'));
        }
    }
}