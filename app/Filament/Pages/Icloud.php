<?php

namespace App\Filament\Pages;

use App\Filament\Actions\Icloud\AddFamilyMemberActions;
use App\Filament\Actions\Icloud\CreateFamilySharingAction;
use App\Filament\Actions\Icloud\LeaveFamilyAction;
use App\Filament\Actions\Icloud\LoginAction;
use App\Filament\Actions\Icloud\UpdateFamilyAction;
use App\Models\Account;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Carbon;
use Modules\AppleClient\Service\AppleBuilder;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;

class Icloud extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.icloud.icloud';

    protected static ?string $title = 'icloud';

    protected static bool $shouldRegisterNavigation = false;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    // 修改路由定义
    protected static ?string $slug = 'icloud/{record}';

    public ?Account $account = null;

    public ?string $activeTab = 'home';

    public ?array $data = [];

    public function mount($record)
    {
        $this->account   = Account::findOrFail($record);
        $this->activeTab = request()->query('tab', 'home');
    }

    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('home')
                ->label('首页')
                ->icon('heroicon-o-home')
                ->url($this->getTabUrl('home')),

            NavigationItem::make('family')
                ->label('家庭共享')
                ->icon('heroicon-o-user-group')
                ->url($this->getTabUrl('family')),
        ];
    }

    protected function getTabUrl(string $tab): string
    {
        return self::getUrl([
            'record' => $this->account->id,
            'tab'    => $tab,
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'account'            => $this->account,
            'lastPasswordUpdate' => Carbon::parse(
                    $this->account->accountManager?->config['lastPasswordChangedDatetime'] ?? null
                )
                    ->format('Y年m月d日') ?? '未知',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getHeading(): string
    {
        return "账号 {$this->account->account} 的设置";
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public function getActions(): array
    {
        return [
            LoginAction::make('icloud_login')
                ->record($this->account)
                ->visible(fn() => true),

            CreateFamilySharingAction::make('create-family-member')
                ->record($this->account)
                ->visible(fn() => !$this->account->belongToFamily && $this->activeTab === 'family'),

            UpdateFamilyAction::make('update-family')
                ->record($this->account)
                ->visible(fn() => $this->activeTab === 'family'),

            LeaveFamilyAction::make('leave-family-member')
                ->record($this->account)
                ->requiresConfirmation(function () {

                    if ($this->account->belongToFamily && $this->account->belongToFamily->organizer === $this->account->dsid) {
                        return '警告：解散家庭将移除所有成员并关闭家庭共享。此操作无法撤销，是否继续？';
                    }

                    return '确定要退出当前家庭吗？退出后将失去所有家庭共享权益。';
                })
                ->visible(fn() => $this->account->belongToFamily && $this->activeTab === 'family'),

            AddFamilyMemberActions::make('add-family-member')
                ->record($this->account)
                ->visible(
                    fn(
                    ) => $this->account->belongToFamily && $this->account->belongToFamily->organizer === $this->account->dsid && $this->activeTab === 'family'
                ),
        ];
    }

    public function removeFromFamily(): void
    {
        try {
            $apple = app(AppleBuilder::class)->build($this->account->toAccount());

            // 调用解散家庭 API
            $apple->getWebResource()
                ->getAppleIdResource()
                ->getAccountManagerResource()
                ->removeFamilyMember();  // 使用正确的方法名

            // 刷新页面
            $this->refreshPage('成员已从家庭中移除');

        } catch (UnauthorizedException $e) {
            $this->handleUnauthorized();
        } catch (\Exception $e) {
            $this->handleError('移除家庭成员失败', $e->getMessage());
        }
    }

    private function refreshPage(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();

        $this->redirect(self::getUrl([
            'record' => $this->account->id,
            'tab'    => $this->activeTab,
        ]));
    }
}
