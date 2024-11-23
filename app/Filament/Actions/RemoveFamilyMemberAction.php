<?php

namespace App\Filament\Actions;

use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Models\Account;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\FamilyService;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use RuntimeException;

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
            ->requiresConfirmation()
            ->action(function (FamilyMember $familyMember) {

                /**
                 * @var FamilyMembersRelationManager $relationManager
                 */
                $relationManager = $this->getLivewire();

                /**
                 * @var Account $account
                 */
                $account = $relationManager->ownerRecord;


                try {
                    $this->handle($account, $familyMember);

                    Notification::make()
                        ->title('成功移除家庭成员')
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

    protected function handle(Account $account, FamilyMember $familyMember): void
    {
        FamilyService::make($account)->removeFamilyMember(
            $familyMember,
        );
    }
}
