<?php

namespace App\Filament\Actions\AppleId;

use App\Models\Account;
use App\Models\AccountManager;
use Exception;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\AccountManager as AccountManagerDto;

class UpdateAccountAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'update-account';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('更新账户')
            ->icon('heroicon-o-user-group')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->successNotificationTitle('更新账户成功')
            ->action(function (\App\Filament\Pages\SecuritySettings $livewire) {

                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handle($account);

                    $this->success();

                } catch (Exception $e) {

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

        /** @var AccountManagerDto $accountManager */
        $accountManager = $apple->getWebResource()
            ->getAppleIdResource()
            ->getAccountManagerResource()
            ->account();

        // 更新或创建 AccountManager 记录
        AccountManager::updateOrCreate(
            ['account_id' => $account->id],
            $accountManager->toArray()
        );
    }
}
