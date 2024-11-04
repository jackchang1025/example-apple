<?php

namespace App\Filament\Resources\SecuritySettingResource\Pages;

use App\Filament\Resources\SecuritySettingResource;
use App\Models\SecuritySetting;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSecuritySetting extends EditRecord
{
    protected static string $resource = SecuritySettingResource::class;

    public function mount(int | string $record = null): void
    {
        $this->record  = SecuritySetting::first() ?? SecuritySetting::create();

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    protected function getActions(): array
    {
        return []; // 移除所有操作按钮
    }

    protected function afterSave(): void
    {
        // 登出用户
        Auth::logout();

        // 清除会话
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    protected function getSavedNotificationMessage(): ?string
    {
        return '安全设置已更新。请使用新的安全入口重新登录。';
    }

    protected function getRedirectUrl(): ?string
    {
        return url("/{$this->record->safe_entrance}/login");
    }
}
