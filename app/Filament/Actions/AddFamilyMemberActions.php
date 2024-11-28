<?php

namespace App\Filament\Actions;

use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Models\Account;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\VerifyCVVRequestDto;

class AddFamilyMemberActions extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'addFamilyMembers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fillForm(function (Table $table): array {

            /**
             * @var FamilyMembersRelationManager $FamilyMembersRelationManager
             */
            $FamilyMembersRelationManager = $table->getLivewire();

            /**
             * @var Account $account
             */
            $account = $FamilyMembersRelationManager->ownerRecord;

            try {

                $familyService            = app(AppleAccountManagerFactory::class)->create($account)->getFamilyService(
                );
                $ITunesAccountPaymentInfo = $familyService->getITunesAccountPaymentInfo();

                return array_merge(
                    $account->makeHidden(['account', 'password'])->toArray(),
                    $ITunesAccountPaymentInfo->toArray()
                );

            } catch (Exception $e) {

                Log::error($e);
                $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();

                $this->halt(); // 停止执行动作

            }

        });

        $this->label('添加家庭共享成员')
            ->icon('heroicon-o-user-group')
            ->modalDescription('创建家庭共享需要绑定支付方式')
            ->successNotificationTitle('添加家庭共享组成功！')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->steps([
                Step::make('select account')
                    ->description('Give the category a unique name')
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

                /**
                 * @var FamilyMembersRelationManager $relationManager
                 */
                $relationManager = $this->getLivewire();

                /**
                 * @var Account $record
                 */
                $record = $relationManager->ownerRecord;

                try {
                    $this->handle($record, $data);

                    $this->success();

                } catch (Exception $e) {
                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();
                }
            });
    }

    /**
     * @param Account $record
     * @param array $data
     * @return void
     * @throws \Illuminate\Validation\ValidationException|\App\Exceptions\Family\FamilyException
     */
    protected function handle(Account $record, array $data): void
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

        app(AppleAccountManagerFactory::class)->create($record)->getFamilyService()
            ->addFamilyMember(
                addAccount: $data['account'],
                addPassword: $data['password'],
                dto: VerifyCVVRequestDto::from($data)
            );

    }
}
