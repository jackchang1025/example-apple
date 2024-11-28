<?php

namespace App\Filament\Actions;

use App\Models\Account;
use Exception;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleAccountManagerFactory;

class LeaveFamilyAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'leaveFamily';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('退出家庭共享')
            ->icon('heroicon-o-arrow-right-on-rectangle')
            // 弹窗标题
            ->modalHeading('退出家庭共享')
            // 弹窗确认按钮
            ->modalSubmitActionLabel('确认退出')
            // 弹窗取消按钮
            ->modalCancelActionLabel('取消')
            // 需要确认
            ->requiresConfirmation('确定退出家庭共享吗？')
            ->successNotificationTitle('成功退出家庭共享组')
            ->action(function () {
                $relationManager = $this->getLivewire();
                $record          = $relationManager->ownerRecord;

                try {
                    $this->handle($record);

                    $this->successRedirectUrl(fn() => url("/admin/accounts/{$record->id}?activeRelationManager=2"))
                        ->success();

                } catch (Exception $e) {
                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();
                }
            });
    }

    protected function handle(Account $account): void
    {
        app(AppleAccountManagerFactory::class)
            ->create($account)
            ->getFamilyService()
            ->leaveFamily();
    }
}
