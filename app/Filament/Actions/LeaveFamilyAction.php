<?php

namespace App\Filament\Actions;

use App\Models\Account;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\FamilyService;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use RuntimeException;

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
            ->action(function () {
                $relationManager = $this->getLivewire();
                $record          = $relationManager->ownerRecord;

                try {
                    $this->handle($record);

                    Notification::make()
                        ->title('成功退出家庭共享组')
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }
            });
    }

    protected function handle(Account $account): void
    {
        FamilyService::make($account)->leaveFamily();
    }
}
