<?php

namespace App\Filament\Actions;

use App\Models\Account;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class LoginAction extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'login';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('登陆')
            ->icon('heroicon-o-user-group')
            ->modalHeading('login')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->extraModalFooterActions([
                \Filament\Actions\Action::make('resendCode')
                    ->label('重新发送验证码')
                    ->color('warning')
                    ->action(function (Account $record) {
                        try {
                            $this->initializeLogin($record);

                            Notification::make()
                                ->title('验证码已重新发送')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->form([

                TextInput::make('account')
                    ->disabled()
                    ->label('Apple ID')->default(fn(Account $record) => $record->account),

                TextInput::make('password')
                    ->disabled()
                    ->label('password')->default(fn(Account $record) => $record->password),

                TextInput::make('authorizationCode')
                    ->required()
                    ->label('授权码')
                    ->placeholder('请输入授权码'),
            ])
            ->beforeFormFilled(function (Account $record) {
                try {
                    $this->initializeLogin($record);
                } catch (Exception $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                    //抛出异常会阻止模态框打开
                    $this->halt();
                }
            })
            ->action(function (Account $record, $data) {
                try {

                    $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);
                    $account = $AppleAccountManagerFactory->create($record);
                    $account->completeAuthentication($data['authorizationCode']);

                    Notification::make()
                        ->title('登陆成功')
                        ->success()
                        ->send();
                } catch (VerificationCodeException|RequestException|FatalRequestException $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }
            });
    }

    /**
     * @param Account $record
     * @return LoginDelegates
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \Modules\AppleClient\Service\Exception\AppleRequestException\LoginRequestException
     */
    public function initializeLogin(Account $record): LoginDelegates
    {
        $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);
        $account                    = $AppleAccountManagerFactory->create($record);

        /**
         * @var LoginDelegates $loginDelegates
         */
        $loginDelegates = $account->initializeLogin();

        if (!$record->dsid && $loginDelegates->dsid) {
            $record->dsid = $loginDelegates->dsid;
            $record->save();
        }

        return $loginDelegates;
    }

}
