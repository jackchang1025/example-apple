<?php

namespace App\Filament\Actions;

use App\Models\Account;
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

                            logger('resendCode');
                            $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);
                            $account                    = $AppleAccountManagerFactory->create($record);
                            $account->initializeLogin();

                            Notification::make()
                                ->title('验证码已重新发送')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->form([
                \Filament\Forms\Components\TextInput::make('authorizationCode')
                    ->required(function (Account $record, $get, $set) {

                        logger('authorizationCode');

                        $needsCode = $get('needs_verification_code');

                        if ($needsCode === null) {
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

                            $needsCode = $loginDelegates->delegates->idsService->status === 5000;
                            $set('needs_verification_code', $needsCode);

                            return $needsCode;
                        }

                        return $needsCode;
                    })
                    ->label('授权码')
                    ->placeholder('请输入授权码'),
            ])
            ->action(function (Account $record, $data) {

                try {

                    logger('action login');

                    $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);
                    $account                    = $AppleAccountManagerFactory->create($record);
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

}
