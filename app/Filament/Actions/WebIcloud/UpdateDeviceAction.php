<?php

namespace App\Filament\Actions\WebIcloud;

use App\Models\Account;
use App\Models\IcloudDevice;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\Devices\Device;

class UpdateDeviceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'updateFamilyMember';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('更新 icloud 设备')
            ->icon('heroicon-o-user-group')
            ->successNotificationTitle('更新 icloud 设备成功')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->action(function () {

                try {
                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handle($account);

                    $this->success();

                } catch (\Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();
                }
            });
    }

    /**
     * @param Account $account
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Contracts\Container\CircularDependencyException
     */
    protected function handle(Account $account): void
    {
        $apple = app(AppleBuilder::class)->build($account->toAccount());

        $apple->getWebResource()
            ->getIcloudResource()
            ->getDevices()
            ->devices
            ->toCollection()
            ->map(function (Device $device) use ($account) {

                IcloudDevice::updateOrCreate(
                    ['udid' => $device->udid, 'account_id' => $account->id],
                    [
                        'serial_number' => $device->serialNumber,
                        'os_version' => $device->osVersion,
                        'model_large_photo_url_2x' => $device->modelLargePhotoURL2x,
                        'model_large_photo_url_1x' => $device->modelLargePhotoURL1x,
                        'name' => $device->name,
                        'imei' => $device->imei,
                        'model' => $device->model,
                        'model_small_photo_url_2x' => $device->modelSmallPhotoURL2x,
                        'model_small_photo_url_1x' => $device->modelSmallPhotoURL1x,
                        'model_display_name' => $device->modelDisplayName,
                    ]
                );
            });

    }
}
