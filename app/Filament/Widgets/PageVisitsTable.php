<?php

namespace App\Filament\Widgets;

use App\Models\PageVisits;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PageVisitsTable extends BaseWidget
{
    use HasWidgetShield;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '访问统计列表';

    public static function getHeading(): string
    {
        return '在线用户统计';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PageVisits::query()->latest('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('uri')
                    ->label('URL')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('页面名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP 地址')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('国家')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('城市')
                    ->searchable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->label('设备类型')
                    ->searchable(),
                Tables\Columns\TextColumn::make('browser')
                    ->label('浏览器')
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform')
                    ->label('操作系统')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('操作系统')
                    ->options(fn () => PageVisits::distinct()->pluck('platform', 'platform')->toArray())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('browser')
                    ->label('浏览器')
                    ->options(fn () => PageVisits::distinct()->pluck('browser', 'browser')->toArray())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('device_type')
                    ->label('设备类型')
                    ->options(fn () => PageVisits::distinct()->pluck('device_type', 'device_type')->toArray())
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
