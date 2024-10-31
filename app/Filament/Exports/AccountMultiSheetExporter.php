<?php

namespace App\Filament\Exports;

use App\Models\Account;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;

class AccountMultiSheetExporter extends Exporter
{
    protected static ?string $model = Account::class;

    /**
     * 定义导出列
     */
    public static function getColumns(): array
    {
        // 这里定义默认的主工作表列
        return [
            ExportColumn::make('account')
                ->label('账号'),
            ExportColumn::make('password')
                ->label('密码'),
            // ... 其他需要导出的列
        ];
    }

    /**
     * 自定义 Excel 样式
     */
    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setFontColor(Color::BLACK)
            ->setBackgroundColor(Color::rgb(230, 230, 230))
            ->setCellAlignment(CellAlignment::LEFT);
    }

    /**
     * 自定义文件名
     */
    public function getFileName(Export $export): string
    {
        return "accounts-export-{$export->getKey()}-".now()->format('Y-m-d-His');
    }

    /**
     * 自定义完成通知消息
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return "已成功导出 {$count} 条账号相关数据";
    }

    /**
     * 优化查询性能
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['devices', 'payment']);
    }

    /**
     * 配置导出任务
     */
    public function getJobBatchName(): ?string
    {
        return '账号数据导出任务';
    }

    /**
     * 设置任务队列
     */
    public function getJobQueue(): ?string
    {
        return 'exports';
    }

    /**
     * 任务重试时间
     */
    public function getJobRetryUntil(): ?\Carbon\CarbonInterface
    {
        return now()->addHours(2);
    }

    /**
     * 定义多个工作表
     */
    public function sheets(): array
    {
        return [
            'Accounts' => $this->getAccountSheet(),
            'Devices'  => $this->getDeviceSheet(),
            'Payments' => $this->getPaymentSheet(),
        ];
    }

    /**
     * 账号工作表
     */
    protected function getAccountSheet(): ExportSheet
    {
        return new class extends ExportSheet {
            public function getColumns(): array
            {
                return [
                    ExportColumn::make('account')
                        ->label('账号'),
                    ExportColumn::make('password')
                        ->label('密码'),
                    ExportColumn::make('status')
                        ->label('状态')
                        ->formatStateUsing(fn($state) => $state?->description()),
                    ExportColumn::make('type')
                        ->label('类型')
                        ->formatStateUsing(fn($state) => $state?->description()),
                    ExportColumn::make('bind_phone')
                        ->label('绑定手机'),
                    ExportColumn::make('bind_phone_address')
                        ->label('绑定手机地址'),
                    ExportColumn::make('created_at')
                        ->label('创建时间')
                        ->formatStateUsing(fn($state) => $state?->format('Y-m-d H:i:s')),
                    ExportColumn::make('updated_at')
                        ->label('更新时间')
                        ->formatStateUsing(fn($state) => $state?->format('Y-m-d H:i:s')),
                ];
            }
        };
    }

    /**
     * 设备工作表
     */
    protected function getDeviceSheet(): ExportSheet
    {
        return new class extends ExportSheet {
            public function getColumns(): array
            {
                return [
                    ExportColumn::make('account.account')
                        ->label('所属账号'),
                    ExportColumn::make('name')
                        ->label('设备名称'),
                    ExportColumn::make('device_id')
                        ->label('设备ID'),
                    ExportColumn::make('device_class')
                        ->label('设备类别'),
                    ExportColumn::make('model_name')
                        ->label('设备型号'),
                    ExportColumn::make('os')
                        ->label('操作系统'),
                    ExportColumn::make('os_version')
                        ->label('系统版本'),
                    ExportColumn::make('supports_verification_codes')
                        ->label('支持验证码')
                        ->formatStateUsing(fn($state) => $state ? '是' : '否'),
                    ExportColumn::make('current_device')
                        ->label('当前设备')
                        ->formatStateUsing(fn($state) => $state ? '是' : '否'),
                    ExportColumn::make('has_apple_pay_cards')
                        ->label('ApplePay')
                        ->formatStateUsing(fn($state) => $state ? '是' : '否'),
                    ExportColumn::make('created_at')
                        ->label('创建时间')
                        ->formatStateUsing(fn($state) => $state?->format('Y-m-d H:i:s')),
                ];
            }

            public function getQuery(): Builder
            {
                return \App\Models\Devices::query()
                    ->with('account');
            }
        };
    }

    /**
     * 支付工作表
     */
    protected function getPaymentSheet(): ExportSheet
    {
        return new class extends ExportSheet {
            public function getColumns(): array
            {
                return [
                    ExportColumn::make('account.account')
                        ->label('所属账号'),
                    ExportColumn::make('payment_method_name')
                        ->label('支付方式'),
                    ExportColumn::make('payment_method_detail')
                        ->label('支付详情'),
                    ExportColumn::make('type')
                        ->label('类型'),
                    ExportColumn::make('is_primary')
                        ->label('主要支付方式')
                        ->formatStateUsing(fn($state) => $state ? '是' : '否'),
                    ExportColumn::make('we_chat_pay')
                        ->label('微信支付')
                        ->formatStateUsing(fn($state) => $state ? '是' : '否'),
                    ExportColumn::make('owner_name')
                        ->label('所有者信息')
                        ->formatStateUsing(function ($state) {
                            if (!$state) {
                                return 'N/A';
                            }

                            return ($state['lastName'] ?? '').' '.($state['firstName'] ?? '');
                        }),
                    ExportColumn::make('billing_address')
                        ->label('账单地址')
                        ->formatStateUsing(function ($state) {
                            if (!$state) {
                                return 'N/A';
                            }

                            return implode(', ', array_filter([
                                $state['line1'] ?? null,
                                $state['city'] ?? null,
                                $state['postalCode'] ?? null,
                            ]));
                        }),
                    ExportColumn::make('created_at')
                        ->label('创建时间')
                        ->formatStateUsing(fn($state) => $state?->format('Y-m-d H:i:s')),
                ];
            }

            public function getQuery(): Builder
            {
                return \App\Models\Payment::query()
                    ->with('account');
            }
        };
    }
}
