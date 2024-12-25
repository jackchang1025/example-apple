<?php

namespace App\Filament\Pages;

use App\Filament\Actions\WebIcloud\LoginAction;
use App\Filament\Actions\WebIcloud\UpdateDeviceAction;
use App\Models\Account;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class WebIcloud extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud';

    protected static string $view = 'filament.pages.web-icloud';

    protected static ?string $title = 'iCloud 设备管理';

    protected static bool $shouldRegisterNavigation = false;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $slug = 'accounts/{record}/icloud';

    public ?Account $account = null;

    public ?string $activeTab = 'devices';

    public function mount($record)
    {
        $this->account   = Account::findOrFail($record);
        $this->activeTab = request()->query('tab', 'devices');
    }

    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('devices')
                ->label('设备管理')
                ->icon('heroicon-o-device-phone-mobile')
                ->url($this->getTabUrl('devices')),

            NavigationItem::make('find-my')
                ->label('查找我的iPhone')
                ->icon('heroicon-o-map-pin')
                ->url($this->getTabUrl('find-my')),

            NavigationItem::make('storage')
                ->label('存储空间')
                ->icon('heroicon-o-circle-stack')
                ->url($this->getTabUrl('storage')),
        ];
    }

    protected function getTabUrl(string $tab): string
    {
        return self::getUrl([
            'record' => $this->account->id,
            'tab'    => $tab,
        ]);
    }

    public function getViewData(): array
    {
        return [
            'account' => $this->account,
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getHeading(): string
    {
        return "账号 {$this->account->account} 的 iCloud 设备管理";
    }

    public function getActions(): array
    {
        return [
            LoginAction::make('login')
                ->record($this->account)
                ->closeModalByClickingAway(false)
                ->visible(fn() => true),

            UpdateDeviceAction::make('update_devices')
                ->record($this->account)
                ->closeModalByClickingAway(false)
                ->visible(fn() => $this->activeTab === 'devices'),
        ];
    }
}
