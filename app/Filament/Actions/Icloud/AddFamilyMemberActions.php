<?php

namespace App\Filament\Actions\Icloud;

use App\Models\Account;
use App\Services\FamilyService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\VerifyCVV\VerifyCVV;

class AddFamilyMemberActions extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'addFamilyMembers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('添加家庭成员')
            ->icon('heroicon-o-user-group')
            ->modalDescription('添加家庭成员需要验证支付方式')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->failureNotificationTitle('添加家庭成员失败')
            ->closeModalByClickingAway(false)
            ->fillForm(function () {
                try {

                    /**
                     * @var Account $account
                     */
                    $account                  = $this->getRecord();
                    $familyService            = FamilyService::make($account);
                    $ITunesAccountPaymentInfo = $familyService->getITunesAccountPaymentInfo();

                    return array_merge(
                        $account->makeHidden(['account', 'password'])->toArray(),
                        $ITunesAccountPaymentInfo->toArray()
                    );
                } catch (Exception $e) {
                    Log::error($e);

                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();

                    //关闭弹窗
                    $this->halt();

                }
            })
            ->steps([
                Step::make('select account')
                    ->description('选择要添加的账号')
                    ->schema([
                        Select::make('account_id')
                            ->options(Account::all()->pluck('account', 'id'))
                            ->rules([
                                function ($get) {
                                    if (!empty($get('account')) && !empty($get('password'))) {
                                        return [];  // 如果已输入账号密码，则不需要选择账号
                                    }

                                    return ['required'];  // 否则必选
                                },
                            ])
                            ->live()
                            ->disabled(fn($get) => !empty($get('account')) && !empty($get('password')))  // 当输入了账号密码时禁用
                            ->dehydrated(fn($get) => empty($get('account')) || empty($get('password'))
                            )  // 当输入了账号密码时不提交此字段
                            ->helperText(
                                fn($get) => !empty($get('account')) && !empty(
                                $get(
                                    'password'
                                )
                                ) ? '已输入账号密码，无需选择' : '当账号和密码都为空时必选'
                            )
                            ->searchable()
                            ->label('选择账号')
                            ->placeholder('请选择账号'),

                        TextInput::make('account')
                            ->rules([
                                function ($get) {
                                    if ($get('account_id')) {
                                        return [];  // 如果选择了account_id，则无需验证
                                    }

                                    return ['required'];  // 否则必填
                                },
                            ])
                            ->live()  // 实时更新
                            ->disabled(fn($get) => (bool)$get('account_id'))
                            ->dehydrated(fn($get) => !$get('account_id'))
                            ->label('账号')
                            ->placeholder('请输入账号'),

                        TextInput::make('password')
                            ->rules([
                                function ($get) {
                                    if ($get('account_id')) {
                                        return [];
                                    }

                                    return ['required'];
                                },
                            ])
                            ->live()  // 实时更新
                            ->disabled(fn($get) => (bool)$get('account_id'))
                            ->dehydrated(fn($get) => !$get('account_id'))
                            ->label('密码')
                            ->placeholder('请输入密码'),
                    ])
                    ->columns(2)->afterValidation(function ($state, $context) {

                        $state['card'] = '123';

                        return $state;
                    }),

                Step::make('Verify Card')
                    ->description('验证卡号')
                    ->schema([

                        TextInput::make('smsSessionID')
                            ->readonly()
                            ->hidden(function ($get) {
                                return !$get('smsSessionID');
                            })
                            ->label('smsSessionID'),

                        TextInput::make('creditCardLastFourDigits')
                            ->readonly()
                            ->hidden(function ($get) {
                                return !$get('creditCardLastFourDigits');
                            })
                            ->label('creditCardLastFourDigits'),

                        TextInput::make('partnerLogin')
                            ->readonly()
                            ->hidden(function ($get) {
                                return !$get('partnerLogin');
                            })
                            ->label('partnerLogin'),

                        TextInput::make('verificationType')
                            ->readonly()
                            ->label('验证类型'),

                        TextInput::make('creditCardId')
                            ->readonly()
                            ->label('信用卡ID'),

                        TextInput::make('challengeReceipt')
                            ->disabled()
                            ->hidden(function ($get) {
                                return !$get('challengeReceipt');
                            })
                            ->label('challengeReceipt'),

                        TextInput::make('securityCode')
                            ->required()
                            ->suffix(function ($get) {
                                return $get('PaymentCardDescription');
                            })
                            ->prefix(function ($get) {

                                $imageUrl = $get('creditCardImageUrl') ?? '';

                                return new HtmlString("<img src=\"{$imageUrl}\" alt=\"card-icon\" class=\"w-5 h-5\">");
                            })
                            ->label('securityCode')
                            ->placeholder('请输入 cvv 密码或者验证码'),
                    ]),
            ])
            ->action(function (array $data) {
                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handle($account, $data);
                    $this->success();
                } catch (Exception $e) {
                    Log::error($e);
                    $this->failure();
                }
            });
    }

    protected function handle(Account $account, array $data): void
    {
        if (!empty($data['account_id'])) {

            $appleId          = Account::findOrFail($data['account_id']);
            $data['account']  = $appleId->account;
            $data['password'] = $appleId->password;
        }

        Validator::make($data, [
            'securityCode'     => 'required',
            'creditCardId'     => 'required',
            'verificationType' => 'required',
            'account'          => 'required',
            'password'         => 'required',
        ])->validated();

        FamilyService::make($account)
            ->addFamilyMember(
                addAccount: $data['account'],
                addPassword: $data['password'],
                data: VerifyCVV::from($data)
            );

    }
}
