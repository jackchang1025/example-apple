<?php

namespace App\Filament\Exports;

use App\Models\Account;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class AccountJsonExporter extends Exporter
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            // 账号基本信息版块
            ExportColumn::make('account')
                ->label('账号'),

            ExportColumn::make('password')
                ->label('密码'),

            ExportColumn::make('status')
                ->label('状态')
                ->formatStateUsing(fn($state) => $state->description()),

            ExportColumn::make('type')
                ->label('类型')
                ->formatStateUsing(fn($state) => $state->description()),

            ExportColumn::make('bind_phone')
                ->label('绑定手机'),

            ExportColumn::make('bind_phone_address')
                ->label('绑定手机地址'),

            ExportColumn::make('devices')
                ->label('设备')
                ->listAsJson(),

            // 支付信息版块 - 使用表格式布局
            ExportColumn::make('payment_info')
                ->label('支付信息')
                ->listAsJson(),


            ExportColumn::make('created_at')
                ->label('创建时间')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),

            ExportColumn::make('updated_at')
                ->label('更新时间')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),
        ];
    }

    public function getJobRetryUntil(): ?\Carbon\CarbonInterface
    {
        return now()->addMinutes(2);
    }

    // 优化性能 - 预加载关联关系
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['devices', 'payment']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = '已成功导出 '.number_format($export->successful_rows).' 条数据';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= '，'.number_format($failedRowsCount).' 条数据导出失败';
        }

        return $body;
    }
}
