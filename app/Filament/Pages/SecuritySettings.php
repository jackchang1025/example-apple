<?php

namespace App\Filament\Pages;

use App\Filament\Actions\AppleId\LoginAction;
use App\Filament\Actions\AppleId\UpdateAccountAction;
use App\Filament\Actions\AppleId\UpdatePaymentAction;
use App\Filament\Actions\AppleId\UpdatePurchaseHistoryAction;
use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Carbon;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Device;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;

class SecuritySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.security-settings';

    protected static ?string $title = '账户设置';

    protected static bool $shouldRegisterNavigation = false;


    // 修改路由定义
    protected static ?string $slug = 'accounts/{record}/security';

    public ?Account $account = null;

    public ?string $activeTab = 'personal';

    public string $birthday = '';

    public string $fullName = '';

    public ?array $data = [];


    public function mount($record)
    {
        $this->account   = Account::findOrFail($record);
        $this->activeTab = request()->query('tab', 'personal');
    }

    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('personal')
                ->label('个人信息')
                ->icon('heroicon-o-user')
                ->url($this->getTabUrl('personal')),

            NavigationItem::make('security')
                ->label('登录和安全')
                ->icon('heroicon-o-shield-check')
                ->url($this->getTabUrl('security')),

            NavigationItem::make('payment')
                ->label('支付方式')
                ->icon('heroicon-o-credit-card')
                ->url($this->getTabUrl('payment')),

            NavigationItem::make('purchase-history')
                ->label('购买历史')
                ->icon('heroicon-o-receipt-refund')
                ->url($this->getTabUrl('purchase-history')),

            NavigationItem::make('subscriptions')
                ->label('订阅')
                ->icon('heroicon-o-rectangle-stack')
                ->url($this->getTabUrl('subscriptions')),

            NavigationItem::make('family')
                ->label('家庭共享')
                ->icon('heroicon-o-user-group')
                ->url($this->getTabUrl('family')),

            NavigationItem::make('devices')
                ->label('设备')
                ->icon('heroicon-o-device-phone-mobile')
                ->url($this->getTabUrl('devices')),

            NavigationItem::make('privacy')
                ->label('隐私')
                ->icon('heroicon-o-lock-closed')
                ->url($this->getTabUrl('privacy')),
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

    protected function getForms(): array
    {
        return [
            'nameForm'     => $this->makeNameForm(),
            'birthdayForm' => $this->makeBirthdayForm(),
        ];
    }

    protected function makeNameForm(): Form
    {
        return $this->makeForm()
            ->schema([
                TextInput::make('fullName')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255)
                    ->default($this->account->accountManager?->config['name']['fullName'] ?? ''),
            ]);
    }

    protected function makeBirthdayForm(): Form
    {
        return $this->makeForm()
            ->schema([
                DatePicker::make('birthday')
                    ->label('生日')
                    ->required()
                    ->displayFormat('Y-m-d')
                    ->default($this->account->accountManager?->config['birthday'] ?? '')
                    ->maxDate(now()),
            ]);
    }

    public function getActions(): array
    {
        return [

            LoginAction::make('apple_id_login')
                ->record($this->account)
                ->closeModalByClickingAway(false)
                ->visible(fn() => true),

            UpdatePurchaseHistoryAction::make('update-purchase-history')
                ->record($this->account)->visible(fn() => $this->activeTab === 'purchase-history'),

            UpdatePaymentAction::make('update-payment')
                ->record($this->account)->visible(fn() => $this->activeTab === 'payment'),

            UpdateAccountAction::make('update-account')
                ->record($this->account)->visible(fn() => $this->activeTab === 'personal'),
        ];
    }

    public function saveName()
    {
        return Notification::make()
            ->title('姓名更新成功')
            ->success()
            ->send();
        try {
            $data = $this->nameForm->getState();

            $config                     = $this->account->accountManager?->config ?? [];
            $config['name']['fullName'] = $data['fullName'];

            $this->account->accountManager()->update([
                'config' => $config,
            ]);

            Notification::make()
                ->title('姓名更新成功')
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'edit-name-modal');
        } catch (Halt $exception) {
            return;
        }
    }

    public function saveBirthday()
    {
        return Notification::make()
            ->title('生日更新成功')
            ->success()
            ->send();

        try {
            $data = $this->birthdayForm->getState();

            $config             = $this->account->accountManager?->config ?? [];
            $config['birthday'] = $data['birthday'];

            $this->account->accountManager()->update([
                'config' => $config,
            ]);

            Notification::make()
                ->title('生日更新成功')
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'edit-birthday-modal');
        } catch (Halt $exception) {
            return;
        }
    }

    public function removeDevice($deviceId)
    {
        try {
            $device = $this->account->devices()->findOrFail($deviceId);

            // 检查是否为当前设备
            if ($device?->current_device) {
                Notification::make()
                    ->title('无法移除当前设备')
                    ->danger()
                    ->send();

                return;
            }

            // 移除设备
            $device?->delete();

            Notification::make()
                ->title('设备已成功移除')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('移除设备失败')
                ->danger()
                ->send();
        }
    }

    public function handleUpdateDevices(): void
    {
        try {

            $apple = app(AppleBuilder::class)->build($this->account->toAccount());

            // 获取设备信息
            $apple->getWebResource()
                ->getAppleIdResource()
                ->getDevicesResource()
                ->getDevicesDetails()
                ->toCollection()
                ->map(fn(Device $device) => $device->deviceDetail->updateOrCreate($apple->getAccount()->model()->id));

            Notification::make()
                ->title('设备信息已更新')
                ->success()
                ->send();

            //刷新页面
            $this->redirect(self::getUrl(['record' => $this->account->id, 'tab' => $this->activeTab]));

        } catch (UnauthorizedException $e) {

            // 显示需要登录的提示
            Notification::make()
                ->title('需要重新登录')
                ->body('请先登录您的 Apple ID')
                ->warning()
                ->persistent()
                ->send();

            // 直接调用 login action
            $this->mountAction('login');

        } catch (\Exception $e) {
            Notification::make()
                ->title('更新设备信息失败')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function handleLoginSuccess(): void
    {
        // 关闭登录表单
        $this->dispatch('close-modal', name: 'login');

        // 显示成功消息
        Notification::make()
            ->title('登录成功')
            ->success()
            ->send();
    }

    private function handleLoginError(\Exception $e): void
    {
        Notification::make()
            ->title('登录失败')
            ->body($e->getMessage())
            ->danger()
            ->persistent()
            ->send();
    }
}
