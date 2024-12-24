<?php

namespace App\Filament\Actions\Icloud;

use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Models\Account;
use App\Models\FamilyMember;
use App\Services\FamilyService;
use Exception;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

class RemoveFamilyMemberAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'removeFamilyMember';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('移除成员')
            ->icon('heroicon-o-user-minus')
            ->modalHeading('确认要移除该家庭成员吗？如果该成员为家庭组织者将移除整个家庭共享成员。')
            ->modalSubmitActionLabel('确认移除')
            ->modalCancelActionLabel('取消')
            ->successNotificationTitle('成功移除家庭成员')
            ->requiresConfirmation()
            ->action(function (FamilyMember $familyMember) {

                /**
                 * @var FamilyMembersRelationManager $relationManager
                 */
                $relationManager = $this->getLivewire();

                /**
                 * @var Account $record
                 */
                $record = $relationManager->ownerRecord;


                try {
                    $this->handle($record, $familyMember);

                    $this->success();

                } catch (Exception $e) {
                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();
                }
            });
    }

    protected function handle(Account $account, FamilyMember $familyMember): void
    {
        FamilyService::make($account)
            ->removeFamilyMember(
            $familyMember,
        );
    }
}
