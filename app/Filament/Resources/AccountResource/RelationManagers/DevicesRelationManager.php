<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('device_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('model_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('os')
                    ->maxLength(255),
                Forms\Components\TextInput::make('os_version')
                    ->maxLength(255),
                // 添加其他你想展示的字段
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ... 其他字段 ...

                Section::make('设备信息')
                    ->schema([
                        RepeatableEntry::make('devices')
                            ->schema([
                                TextEntry::make('device_id')->label('设备 ID'),
                                TextEntry::make('name')->label('设备名称'),
                                TextEntry::make('model_name')->label('型号'),
                                TextEntry::make('os')->label('操作系统'),
                                TextEntry::make('os_version')->label('系统版本'),
                                TextEntry::make('serial_number')->label('序列号'),
                                TextEntry::make('imei')->label('IMEI'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')->label('设备 ID'),
                Tables\Columns\TextColumn::make('name')->label('设备名称'),
                Tables\Columns\TextColumn::make('model_name')->label('型号'),
                Tables\Columns\TextColumn::make('os')->label('操作系统'),
                Tables\Columns\TextColumn::make('os_version')->label('系统版本'),
                Tables\Columns\TextColumn::make('serial_number')->label('序列号'),
                Tables\Columns\TextColumn::make('imei')->label('IMEI'),
                // 添加其他你想在表格中显示的列
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
