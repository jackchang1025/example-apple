<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

class IcloudDevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'IcloudDevice';

    protected static ?string $title = 'iCloud 设备';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serial_number')
                    ->label('序列号')
                    ->required(),
                Forms\Components\TextInput::make('os_version')
                    ->label('系统版本')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('设备名称')
                    ->required(),
                Forms\Components\TextInput::make('imei')
                    ->label('IMEI')
                    ->required(),
                Forms\Components\TextInput::make('model')
                    ->label('设备型号')
                    ->required(),
                Forms\Components\TextInput::make('udid')
                    ->label('UDID')
                    ->required(),
                Forms\Components\TextInput::make('model_display_name')
                    ->label('显示名称')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('udid')
                    ->label('UDID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('serial_number')
                    ->label('序列号')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('设备名称')
                    ->searchable(),

                Tables\Columns\TextColumn::make('model_display_name')
                    ->label('显示名称')
                    ->searchable(),

                Tables\Columns\TextColumn::make('os_version')
                    ->label('系统版本')
                    ->sortable(),

                Tables\Columns\TextColumn::make('imei')
                    ->label('IMEI')
                    ->searchable(),


                Tables\Columns\ImageColumn::make('model_large_photo_url_2x')
                    ->label('设备图片'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('设备基本信息')
                    ->schema([
                        TextEntry::make('serial_number')
                            ->label('序列号'),
                        TextEntry::make('name')
                            ->label('设备名称'),
                        TextEntry::make('model_display_name')
                            ->label('显示名称'),
                        TextEntry::make('os_version')
                            ->label('系统版本'),
                        TextEntry::make('imei')
                            ->label('IMEI'),
                        TextEntry::make('udid')
                            ->label('UDID'),
                    ])->columns(2),

                Section::make('设备图片')
                    ->schema([
                        TextEntry::make('model_large_photo_url_2x')
                            ->label('大图 2x')
                            ->url(fn($record) => $record->model_large_photo_url_2x)
                            ->openUrlInNewTab(),
                        TextEntry::make('model_large_photo_url_1x')
                            ->label('大图 1x')
                            ->url(fn($record) => $record->model_large_photo_url_1x)
                            ->openUrlInNewTab(),
                        TextEntry::make('model_small_photo_url_2x')
                            ->label('小图 2x')
                            ->url(fn($record) => $record->model_small_photo_url_2x)
                            ->openUrlInNewTab(),
                        TextEntry::make('model_small_photo_url_1x')
                            ->label('小图 1x')
                            ->url(fn($record) => $record->model_small_photo_url_1x)
                            ->openUrlInNewTab(),
                    ])->columns(2),
            ]);
    }
}
