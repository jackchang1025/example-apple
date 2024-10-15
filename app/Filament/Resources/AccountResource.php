<?php

namespace App\Filament\Resources;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use App\Filament\Resources\AccountLogsResource\RelationManagers\AccountRelationManager;
use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $label = '账号';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('account')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(AccountStatus::getDescriptionValuesArray())
                    ->required(),
                Forms\Components\TextInput::make('bind_phone')
                    ->required(),
                Forms\Components\TextInput::make('bind_phone_address')
                    ->required(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AccountRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('account')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('password')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn (AccountStatus $state): string => $state->description())
                    ->color(fn (AccountStatus $state): string => $state->color())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn(AccountType $state): string => $state->description())
                    ->color(fn(AccountType $state): string => $state->color())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bind_phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bind_phone_address')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable()
                    ->dateTime(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                ->options(AccountStatus::getDescriptionValuesArray())
                ->placeholder('选择状态'),

                Tables\Filters\SelectFilter::make('type')
                    ->options(AccountType::getDescriptionValuesArray())
                    ->placeholder('选择类型'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('基本信息')
                    ->schema([
                        TextEntry::make('account')->label('账号'),
                        TextEntry::make('password')->label('密码'),
                        TextEntry::make('status')->label('状态')
                            ->formatStateUsing(fn (AccountStatus $state): string => $state->description())
                            ->color(fn (AccountStatus $state): string => $state->color()),

                        TextEntry::make('type')->label('类型')
                            ->formatStateUsing(fn(AccountType $state): string => $state->description())
                            ->color(fn(AccountType $state): string => $state->color()),


                        TextEntry::make('bind_phone')->label('绑定手机号码'),
                        TextEntry::make('bind_phone_address')->label('绑定手机号码地址'),
                        TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2)
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
            'view' => Pages\ViewAccount::route('/{record}'),
        ];
    }
}
