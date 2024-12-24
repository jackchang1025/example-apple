<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamilyMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'familyMembers';

    protected static ?string $title = '家庭成员';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('全名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('apple_id')
                    ->label('Apple ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dsid')
                    ->label('dsid')
                    ->searchable(),

                Tables\Columns\TextColumn::make('age_classification')
                    ->label('年龄分类'),

                Tables\Columns\IconColumn::make('has_parental_privileges')
                    ->label('家长权限')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_screen_time_enabled')
                    ->label('屏幕使用时间')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_ask_to_buy_enabled')
                    ->label('购买请求')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_share_purchases_enabled')
                    ->label('购买项目共享')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_share_my_location_enabled')
                    ->label('位置共享')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('加入时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

            ])
            ->bulkActions([
                //
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->distinct();
            });
    }
}
