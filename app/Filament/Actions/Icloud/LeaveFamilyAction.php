<?php

namespace App\Filament\Actions\Icloud;

use App\Models\Account;
use App\Services\FamilyService;
use Exception;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

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
            ->requiresConfirmation('确定要退出当前家庭吗？退出后将失去所有家庭共享权益。？')
            ->successNotificationTitle('成功退出家庭共享组')
            ->action(function () {

                /**
                 * @var Account $account
                 */
                $account = $this->getRecord();

                try {
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
        FamilyService::make($account)
            ->leaveFamily();
    }
}
