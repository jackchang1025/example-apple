<?php

namespace App\Filament\Exports;

use App\Models\Account;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class AccountTableExporter extends Exporter
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            // 基本信息列
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


            // 设备信息列(表格式展示)
            // 修改设备信息列的格式化方式
            ExportColumn::make('devices_table')
                ->label('设备信息')
                ->formatStateUsing(function ($record) {
                    if ($record->devices->isEmpty()) {
                        return '暂无设备';
                    }

                    return
                        static::buildDeviceTableHeader().
                        static::buildDeviceTableRows($record->devices);
                }),

            // 支付信息列(表格式展示)
            // 修改支付信息列的格式化方式
            ExportColumn::make('payment_info')
                ->label('支付信息')
                ->formatStateUsing(function ($record) {
                    if (!$record->payment) {
                        return '暂无支付信息';
                    }

                    $payment = $record->payment;
                    $parts   = [];

                    // 基本信息
                    $parts[] = sprintf(
                        '支付方式:%s 类型:%s 主要支付方式:%s 微信支付:%s',
                        $payment->payment_method_name ?? 'N/A',
                        $payment->type ?? 'N/A',
                        $payment->is_primary ? '是' : '否',
                        $payment->we_chat_pay ? '是' : '否'
                    );

                    // 所有者信息
                    if ($payment->owner_name) {
                        $parts[] = sprintf(
                            '所有者: %s %s',
                            $payment->owner_name['lastName'] ?? 'N/A',
                            $payment->owner_name['firstName'] ?? 'N/A'
                        );
                    }

                    // 账单地址
                    if ($payment->billing_address) {
                        $parts[] = sprintf(
                            '地址: %s, %s, %s',
                            $payment->billing_address['line1'] ?? 'N/A',
                            $payment->billing_address['city'] ?? 'N/A',
                            $payment->billing_address['postalCode'] ?? 'N/A'
                        );
                    }

                    return implode(' | ', $parts);
                }),

            ExportColumn::make('created_at')
                ->label('创建时间')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),

            ExportColumn::make('updated_at')
                ->label('更新时间')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),
        ];
    }

    protected static function buildDeviceTableHeader(): string
    {
        // 使用更少的空格和更简单的分隔符
        $header = [
            '设备名称',
            '设备型号',
            '系统版本',
            '验证码支持',
            '当前设备',
            'ApplePay',
        ];

        // 使用空格分隔，不使用换行符
        return implode(' | ', $header).' ';
    }

    protected static function buildDeviceTableRows($devices): string
    {
        $rows = [];
        foreach ($devices as $device) {
            $rows[] = static::buildDeviceTableRow($device);
        }

        return implode('  ', $rows);
    }

    protected static function buildDeviceTableRow($device): string
    {
        // 定义行数据
        $row = [
            str_pad($device->name ?? 'N/A', 15),
            str_pad($device->model_name ?? 'N/A', 15),
            str_pad(($device->os.' '.$device->os_version) ?? 'N/A', 15),
            str_pad($device->supports_verification_codes ? '是' : '否', 10),
            str_pad($device->current_device ? '是' : '否', 10),
            str_pad($device->device_id ?? 'N/A', 20),
            str_pad($device->has_apple_pay_cards ? '是' : '否', 10),
        ];

        return implode(' | ', $row)."\n";
    }

    public function getJobRetryUntil(): ?\Carbon\CarbonInterface
    {
        return now()->addMinutes(2);
    }

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

    protected static function buildPaymentInfo($payment): string
    {
        $info = "【基本信息】\n";
        $info .= str_pad('支付方式: '.($payment->payment_method_name ?? 'N/A'), 30);
        $info .= str_pad('类型: '.($payment->type ?? 'N/A'), 20)."\n";
        $info .= str_pad('主要支付方式: '.($payment->is_primary ? '是' : '否'), 30);
        $info .= str_pad('微信支付: '.($payment->we_chat_pay ? '是' : '否'), 20)."\n";

        if ($payment->owner_name) {
            $info .= "\n【所有者信息】\n";
            $info .= str_pad('姓: '.($payment->owner_name['lastName'] ?? 'N/A'), 30);
            $info .= str_pad('名: '.($payment->owner_name['firstName'] ?? 'N/A'), 30)."\n";
        }

        if ($payment->billing_address) {
            $info .= "\n【账单地址】\n";
            $info .= "地址: ".($payment->billing_address['line1'] ?? 'N/A')."\n";
            $info .= "城市: ".($payment->billing_address['city'] ?? 'N/A')."\n";
            $info .= "邮编: ".($payment->billing_address['postalCode'] ?? 'N/A')."\n";
        }

        return $info;
    }
}
