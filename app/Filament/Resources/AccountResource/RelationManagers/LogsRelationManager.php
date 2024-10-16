<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $recordTitleAttribute = 'action';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewAccount::class;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
                TextEntry::make('action')
                    ->label('操作'),
                TextEntry::make('description')
                    ->label('描述'),
                TextEntry::make('created_at')
                    ->label('创建时间')
                    ->dateTime(),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('操作')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
