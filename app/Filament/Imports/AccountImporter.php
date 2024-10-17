<?php

namespace App\Filament\Imports;

use App\Apple\Enums\AccountType;
use App\Jobs\ProcessAccountImport;
use App\Models\Account;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Phone\Rules\EmailOrPhoneValidationRule;
use Modules\Phone\Service\PhoneNumberFactory;
use Propaganistas\LaravelPhone\Exceptions\NumberFormatException;

class AccountImporter extends Importer
{
    protected static ?string $model = Account::class;

    protected static ?PhoneNumberFactory $phoneNumberFactory = null;

    public static function getColumns(): array
    {
        return [

            ImportColumn::make('account')
                ->requiredMapping()
                ->rules(['required', 'max:255', new EmailOrPhoneValidationRule(self::getPhoneNumberFactory())])
                ->helperText('账号只能是手机号码和邮箱格式，在导入的时候使用 +86 这种国际号码格式')
                ->example(['user@example.com', "\t+8613800138000"]),

            ImportColumn::make('password')
                ->requiredMapping()
                ->example(['123456', '123456'])
                ->rules(['required', 'min:6', 'max:50']),

            ImportColumn::make('bind_phone')
                ->rules(['nullable', 'phone:AUTO'])
                ->helperText('账号只能是手机号码，在导入的时候使用 +86 这种国际号码格式')
                ->example(["\t+8613800138000", "\t+85297403063"]),

            ImportColumn::make('bind_phone_address')
                ->rules(['nullable', 'url', 'max:255'])
                ->helperText('账号只能是一个有效的URL，例如 https://example.com')
                ->example(['https://example.com', 'https://example.com']),
        ];
    }

    public static function getPhoneNumberFactory(): PhoneNumberFactory
    {
        return self::$phoneNumberFactory ??= new PhoneNumberFactory();
    }

    public function getValidationMessages(): array
    {
        return [
            'account.required'       => '账号是必填项。',
            'account.regex'          => '账号格式无效，必须是有效的邮箱或手机号（支持国际格式）。',
            'password.required'      => '密码是必填项。',
            'password.min'           => '密码长度至少为6个字符。',
            'bind_phone.regex'       => '绑定手机号格式无效，必须是有效的手机号（支持国际格式）。',
            'bind_phone_address.url' => '绑定手机地址必须是有效的URL。',
        ];
    }

    /**
     * @throws RowImportFailedException
     */
    public function resolveRecord(): ?Account
    {
        $account = $this->validateAccount($this->data['account']);

        $this->data['account'] = $account;

        // 检查是否存在重复账号
        $existingAccount = Account::where('account', $account)->exists();

        if ($existingAccount) {
            // 如果账号已存在,返回 null 以忽略此记录
            throw new RowImportFailedException("账号 {$account} 已存在，无法导入。");
        }

        return new Account([
            'type'    => AccountType::IMPORTED->value,
            'account' => $account,
        ]);
    }

    /**
     * @param string $account
     * @return string
     * @throws RowImportFailedException
     */
    protected function validateAccount(string $account): string
    {
        $validator = Validator::make(['email' => $account], [
            'email' => 'email',
        ]);

        // 不是有效的邮箱,那就是手机号
        if ($validator->fails()) {
            return $this->formatPhone($account);
        }

        return $account;
    }

    /**
     * @param string $phone
     * @return string
     * @throws RowImportFailedException
     */
    protected function formatPhone(string $phone): string
    {
        try {
            return self::getPhoneNumberFactory()->create($phone)->format();
        } catch (NumberFormatException $e) {
            throw new RowImportFailedException("手机号格式无效，必须是有效的手机号（支持国际格式）");
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = "账号导入已完成，成功导入 ".number_format($import->successful_rows)." 个账号。";

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= " ".number_format($failedRowsCount)." 个账号导入失败。";
        }

        return $body;
    }

    public function afterCreate(): void
    {
        // 获取刚刚创建的记录
        Log::info('Account afterCreate: ', [$this->record]);
        ProcessAccountImport::dispatch($this->record);
    }
}
