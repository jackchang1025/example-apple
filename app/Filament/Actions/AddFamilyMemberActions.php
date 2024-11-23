<?php

namespace App\Filament\Actions;

use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Models\Account;
use App\Services\FamilyService;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Validator;

class AddFamilyMemberActions extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'addFamilyMembers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('添加家庭共享成员')
            ->icon('heroicon-o-user-group')
            ->modalDescription('创建家庭共享需要绑定支付方式')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->steps([
                Step::make('Name')
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

                        TextInput::make('card')
                            ->readonly()
                            ->label('支付卡号')
                            ->default(function (TextInput $component) {

                                /**
                                 * @var FamilyMembersRelationManager $FamilyMembersRelationManager
                                 */
                                $FamilyMembersRelationManager = $component->getLivewire();

                                /**
                                 * @var Account $record
                                 */
                                $record = $FamilyMembersRelationManager->ownerRecord;

                                try {

                                    return FamilyService::make($record)->getITunesAccountPaymentInfo(
                                    )->creditCardLastFourDigits;

                                } catch (Exception $e) {
                                    Notification::make()
                                        ->title($e->getMessage())
                                        ->warning()
                                        ->persistent()
                                        ->send();

                                    $this->halt(); // 停止执行动作

                                    return null;
                                }
                            }),

                        TextInput::make('cvv')
                            ->required()
                            ->maxLength(3)
                            ->rules('size:3')
                            ->label('cvv')
                            ->placeholder('请输入 cvv 密码'),
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

                    Notification::make()
                        ->title('添加家庭共享组成功')
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }
            });
    }

    /**
     * @param Account $record
     * @param array $data
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handle(Account $record, array $data): void
    {
        if (!empty($data['account_id'])) {

            $appleId          = Account::findOrFail($data['account_id']);
            $data['account']  = $appleId->account;
            $data['password'] = $appleId->password;
        }

        Validator::make($data, [
            'card'     => 'required',
            'cvv'      => 'required',
            'account'  => 'required',
            'password' => 'required',
        ])->validated();


        FamilyService::make($record)->addFamilyMember(
            $data['card'],
            $data['cvv'],
            $data['account'],
            $data['password']
        );

    }
}
