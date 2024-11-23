<?php

namespace App\Filament\Actions;

use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Models\Account;
use App\Services\FamilyService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class UpdateFamilyAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'updateFamilyMember';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('更新家庭共享成员')
            ->icon('heroicon-o-user-group')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->action(function () {

                try {

                    /**
                     * @var FamilyMembersRelationManager $relationManager
                     */
                    $relationManager = $this->getLivewire();

                    /**
                     * @var Account $record
                     */
                    $record = $relationManager->ownerRecord;

                    $this->handle($record);

                    Notification::make()
                        ->title('更新家庭共享组成功')
                        ->success()
                        ->send();

                } catch (\Exception $e) {

                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }
            });
    }

    protected function handle(Account $account): void
    {
        $familyService = FamilyService::make($account);

        $familyInfo = $familyService->getFamilyDetails();

        //delete family
        $familyService->deleteFamilyData();

        // Update family information
        $familyService->updateFamilyData($familyInfo);

    }
}
