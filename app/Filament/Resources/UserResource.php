<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BezhanSalleh\FilamentShield\FilamentShield;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    public static function getNavigationLabel(): string
    {
        return trans('filament-users::user.resource.label');
    }

    public static function getPluralLabel(): string
    {
        return trans('filament-users::user.resource.label');
    }

    public static function getLabel(): string
    {
        return trans('filament-users::user.resource.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-users.group');
    }

    public function getTitle(): string
    {
        return trans('filament-users::user.resource.title.resource');
    }

    public static function form(Form $form): Form
    {
        $rows = [
            TextInput::make('name')
                ->required()
                ->label(trans('filament-users::user.resource.name')),
            TextInput::make('email')
                ->email()
                ->required()
                ->label(trans('filament-users::user.resource.email')),

            TextInput::make('password')
                ->label(trans('filament-users::user.resource.password'))
                ->password()
                ->maxLength(255)
                ->required(fn(string $operation): bool => $operation === 'create')
                ->autocomplete('new-password')
                ->dehydrateStateUsing(static function ($state, User $user) {

                    // 如果是编辑状态且密码没有改变
                    if ($state === $user->password) {
                        return $state; // 返回原密码
                    }

                    // 如果密码为空且是编辑状态
                    if (empty($state)) {
                        return $user->password; // 返回原密码
                    }

                    // 否则加密新密码
                    return Hash::make($state);
                })
                ->helperText(function ($context) {
                    if ($context === 'edit') {
                        return '留空表示保持原密码不变。填写新密码将会覆盖原密码。';
                    }

                    return '请输入至少8位的密码。';
                }),

            Forms\Components\DateTimePicker::make('valid_from')
                ->label('有效期开始')
                ->nullable(),

            Forms\Components\DateTimePicker::make('valid_until')
                ->label('有效期结束')
                ->nullable()
                ->afterOrEqual('valid_from'),

        ];


        if (config('filament-users.shield') && class_exists(FilamentShield::class)) {
            $rows[] = Forms\Components\Select::make('roles')
                ->multiple()
                ->preload()
                ->relationship('roles', 'name')
                ->label(trans('filament-users::user.resource.roles'));
        }


        $rows[] = Forms\Components\Toggle::make('is_active')
            ->label('是否激活')
            ->onIcon('heroicon-m-bolt')
            ->offIcon('heroicon-m-user')
            ->default(true);

        $form->schema($rows);

        return $form;
    }

    public static function table(Table $table): Table
    {
        if (class_exists(\STS\FilamentImpersonate\Tables\Actions\Impersonate::class) && config(
                'filament-users.impersonate'
            )) {
            $table->actions([\STS\FilamentImpersonate\Tables\Actions\Impersonate::make('impersonate')]);
        }
        $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label(trans('filament-users::user.resource.id')),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(trans('filament-users::user.resource.name')),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable()
                    ->label(trans('filament-users::user.resource.email')),
                IconColumn::make('email_verified_at')
                    ->boolean()
                    ->sortable()
                    ->searchable()
                    ->label(trans('filament-users::user.resource.email_verified_at')),
                TextColumn::make('created_at')
                    ->label(trans('filament-users::user.resource.created_at'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(trans('filament-users::user.resource.updated_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('verified')
                    ->label(trans('filament-users::user.resource.verified'))
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Tables\Filters\Filter::make('unverified')
                    ->label(trans('filament-users::user.resource.unverified'))
                    ->query(fn(Builder $query): Builder => $query->whereNull('email_verified_at')),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                ]),
            ]);
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
