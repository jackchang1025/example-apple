<?php

namespace App\Filament\Imports;

use App\Models\Phone;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;

class PhoneImporter extends Importer
{
    protected static ?string $model = Phone::class;

    public static function getColumns(): array
    {
        return [

            ImportColumn::make('phone')
                ->requiredMapping()
                ->rules([
                    'required',
                    'max:255',
                    'phone:AUTO',
                ])->examples(["\t+8613065851245", "\t+16134589339"]),

            ImportColumn::make('phone_address')
                ->requiredMapping()
                ->rules(['required','url', 'max:255'])
                ->examples(['https://api.sms-999.com/api/sms/record?key=da8b40973b0310592445bc211c13bc57', 'https://api.sms-999.com/api/sms/record?key=da8b40973b0310592445bc211c13bc57']),
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'country_code.required' => 'The country code is required.',
            'country_code.string' => 'The country code must be a string.',
            'country_code.max' => 'The country code must not exceed 2 characters.',
            'phone.required' => 'The phone number is required.',
            'phone.string' => 'The phone number must be a string.',
            'phone.max' => 'The phone number must not exceed 255 characters.',
            'phone_address.required' => 'The phone address is required.',
            'phone_address.url' => 'The phone address must be a valid URL.',
            'phone_address.max' => 'The phone address must not exceed 255 characters.',
        ];
    }

    /**
     * @return Phone|null
     * @throws RowImportFailedException
     */
    public function resolveRecord(): ?Phone
    {
        $phone    = $this->data['phone'];
        $existing = Phone::where('phone', $phone)->exists();

        if ($existing) {
            // 如果账号已存在,返回 null 以忽略此记录
            throw new RowImportFailedException("{$phone} 已存在，无法导入。");
        }

        $this->data['country_code'] = null;
        $this->data['country_dial_code'] = null;
        return new Phone($this->data);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your phone import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        $import->failedRows->map(function (FailedImportRow $row) use (&$body){

            $body .= ' ' . $row->validation_error. ' failed to import.';
        });

        return $body;
    }
}
