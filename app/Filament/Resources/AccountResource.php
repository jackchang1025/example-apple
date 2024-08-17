<?php

namespace App\Filament\Resources;

use App\Apple\Service\Enums\AccountStatus;
use App\Filament\Resources\AccountLogsResource\RelationManagers\AccountRelationManager;
use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

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
