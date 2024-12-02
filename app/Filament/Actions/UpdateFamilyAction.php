<?php

namespace App\Filament\Actions;

use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Models\Account;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleAccountManagerFactory;

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
            ->successNotificationTitle('更新家庭共享组成功')
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

                    $this->success();

                } catch (\Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();
                }
            });
    }

    protected function handle(Account $account): void
    {
        $familyService = app(AppleAccountManagerFactory::class)
            ->create($account)
            ->getFamilyService();

        $familyInfo = $familyService->getFamilyDetails();

        //delete family
        $familyService->deleteFamilyData();

        // Update family information
        $familyService->updateFamilyData($familyInfo);

    }
}
