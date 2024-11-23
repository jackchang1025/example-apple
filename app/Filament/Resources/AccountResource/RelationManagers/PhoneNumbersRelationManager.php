<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhoneNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'phoneNumbers';

    protected static ?string $title = '电话号码';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('raw_number')
                    ->label('电话号码')
                    ->required()
                    ->tel(),

                Forms\Components\TextInput::make('country_code')
                    ->label('国家代码')
                    ->required(),

                Forms\Components\TextInput::make('country_dial_code')
                    ->label('国家拨号代码')
                    ->required(),

                Forms\Components\Toggle::make('is_vetted')
                    ->label('已验证')
                    ->default(false),

                Forms\Components\Toggle::make('is_trusted')
                    ->label('可信号码')
                    ->default(false),

                Forms\Components\Toggle::make('is_login_handle')
                    ->label('登录句柄')
                    ->default(false),

                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'primary'   => '主要',
                        'secondary' => '次要',
                        'business'  => '商务',
                    ])
                    ->default('primary')
                    ->required(),

                Forms\Components\KeyValue::make('additional_data')
                    ->label('额外数据')
                    ->keyLabel('属性')
                    ->valueLabel('值')
                    ->reorderable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('raw_number')
                    ->label('电话号码')
                    ->searchable(),

                Tables\Columns\TextColumn::make('country_code')
                    ->label('国家代码'),

                Tables\Columns\IconColumn::make('is_vetted')
                    ->label('已验证')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_trusted')
                    ->label('可信号码')
                    ->boolean(),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'primary' => 'success',
                        'secondary' => 'info',
                        'business' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
