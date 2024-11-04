<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Saade\FilamentLaravelLog\Pages\ViewLog;

class LogView extends ViewLog
{
    use HasPageShield;

    // 修改导航组（如果需要）
    protected static ?string $navigationGroup = 'System Log';

    // 确保使用与原始类相同的 slug
    protected static ?string $slug = 'logs';


    // 修改页面标题
    public static function getNavigationLabel(): string
    {
        return '日志';
    }

    // 修改页面标题（显示在页面顶部）
    public function getTitle(): string
    {
        return '日志';
    }

    // 可选：添加一个小标题
    public function getSubheading(): ?string
    {
        return 'View and manage your application logs';
    }

}
