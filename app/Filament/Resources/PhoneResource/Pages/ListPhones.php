<?php

namespace App\Filament\Resources\PhoneResource\Pages;

use App\Filament\Imports\PhoneImporter;
use App\Filament\Resources\PhoneResource;
use App\Jobs\ImportCsvJob;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\HelperText;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Symfony\Component\Intl\Countries;
use Propaganistas\LaravelPhone\PhoneNumber;
use App\Models\Phone;
class ListPhones extends ListRecords
{
    protected static string $resource = PhoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('batch_import')
            ->label('批量导入')
            ->icon('heroicon-o-document-plus')
            ->form([
                Forms\Components\Textarea::make('phones_data')
                    ->label('手机号码列表')
                    ->required()
                    ->helperText('请输入手机号码和URI，每行一个，使用 | 或 ---- 分隔。例如：
+16514779187|https://api.sms-999.com/api/sms/record?key=e0c5a4b5cb15510330d2f97c35d61b3d
或
+16514779187----https://api.sms-999.com/api/sms/record?key=e0c5a4b5cb15510330d2f97c35d61b3d')
                    ->rows(10),
                Forms\Components\Select::make('country_code')
                    ->label('国家（可选）')
                    ->options(Countries::getNames('zh-CN'))
                    ->searchable()
                    ->nullable()
                    ->helperText('如果选择，将为所有导入的号码设置此国家。否则，将尝试从号码自动推断。'),
            ])
            ->action(function (array $data) {
                $lines = explode("\n", trim($data['phones_data']));
                $selectedCountryCodeAlpha2 = $data['country_code'] ?? null; // Note: The value is Alpha-2 code
                $successCount = 0;
                $failedEntries = [];

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }

                    // 支持两种分隔符: | 和 ----
                    $separator = '----';
                    if (strpos($line, '|') !== false) {
                        $separator = '|';
                    }

                    $parts = explode($separator, $line);
                    if (count($parts) !== 2) {
                        $failedEntries[] = $line . ' (格式错误，请使用 | 或 ---- 分隔手机号和URI)';
                        continue;
                    }

                    $phone = trim($parts[0]);
                    $phoneAddress = trim($parts[1]);

                    // Handle URL prefix for phone_address
                    // Remove 'https://' prefix if it exists since the form field has a prefix
                    if (str_starts_with($phoneAddress, 'https://')) {
                        $phoneAddress = substr($phoneAddress, 8);
                    }

                    // URL解码，确保特殊字符不被编码
                    $phoneAddress = rawurldecode($phoneAddress);

                    try {
                        

                        $countryCode = null;
                        $countryCodeAlpha3 = null;
                        $countryDialCode = null;


                        if ($selectedCountryCodeAlpha2) {
                            // User selected a country
                            try {

                                $phoneService = new PhoneNumber($phone, [$selectedCountryCodeAlpha2]);
                                $countryCode = $phoneService->getCountry();
                                // $countryCodeAlpha3 = Countries::getAlpha3Code($countryCode);
                                
                                $countryDialCode = $phoneService->toLibPhoneObject()?->getCountryCode();

                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('国家代码错误')
                                    ->body("无法处理 {$phone} 的选定国家代码: {$selectedCountryCodeAlpha2} - ")
                                    ->warning()
                                    ->send();

                                continue;
                            }
                        }

                        if(!str_starts_with($phone,'+')){
                            $phone = '+'.$countryDialCode.$phone;
                        }

                        // Check if phone already exists
                        if (Phone::where('phone', $phone)->exists()) {
                            $failedEntries[] = $phone . ' (已存在)';
                            continue;
                        }

                        // Create new phone record
                        Phone::create([
                            'phone' => $phone,
                            'phone_address' => $phoneAddress,
                            'status' => Phone::STATUS_NORMAL,
                            'country_code' => $countryCode,
                            // 'country_code_alpha3' => $countryCodeAlpha3,
                            'country_dial_code' => $countryDialCode,
                        ]);

                        $successCount++;
                    } catch (\Exception $e) {
                        $failedEntries[] = $phone . ' (' . $e->getMessage() . ')';
                    }
                }

                // Show notification
                $message = "成功导入 {$successCount} 个手机号码";
                if (!empty($failedEntries)) {
                    $message .= "，" . count($failedEntries) . " 个导入失败：" . implode(', ', array_slice($failedEntries, 0, 5));
                    if (count($failedEntries) > 5) {
                        $message .= "...等";
                    }
                }

                Notification::make()
                    ->title('批量导入完成')
                    ->body($message)
                    ->success()
                    ->send();

                // Refresh the page to show new records
                $this->resetTable();
            }),
        ];
    }
}
