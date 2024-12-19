<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseHistory';

    protected static ?string $title = '购买历史记录';
    protected static ?string $recordTitleAttribute = 'purchase_id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('purchase_id')
            ->columns([
                Tables\Columns\TextColumn::make('purchase_id')
                    ->label('购买ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('web_order_id')
                    ->label('订单ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_amount')
                    ->label('发票金额')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('购买时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_pending_purchase')
                    ->label('待处理')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('purchase_date', 'desc');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('购买信息')
                ->schema([
                    TextEntry::make('purchase_id')
                        ->label('购买ID'),
                    TextEntry::make('web_order_id')
                        ->label('订单ID'),
                    TextEntry::make('invoice_amount')
                        ->label('发票金额'),
                    TextEntry::make('purchase_date')
                        ->label('购买时间')
                        ->dateTime(),
                    TextEntry::make('dsid')
                        ->label('DSID'),
                    TextEntry::make('invoice_date')
                        ->label('发票日期')
                        ->dateTime(),
                    TextEntry::make('estimated_total_amount')
                        ->label('预计总金额'),
                    IconEntry::make('is_pending_purchase')
                        ->label('是否待处理')
                        ->boolean(),
                ])
                ->columns(2),

            Section::make('购买项目明细')
                ->schema([
                    RepeatableEntry::make('plis')
                        ->schema([
                            TextEntry::make('item_id')
                                ->label('项目ID'),
                            TextEntry::make('storefront_id')
                                ->label('商店前端ID'),
                            TextEntry::make('adam_id')
                                ->label('Adam ID'),
                            TextEntry::make('guid')
                                ->label('GUID'),
                            TextEntry::make('amount_paid')
                                ->label('支付金额'),
                            TextEntry::make('pli_date')
                                ->label('PLI日期')
                                ->dateTime(),
                            IconEntry::make('is_free_purchase')
                                ->label('是否免费购买')
                                ->boolean(),
                            IconEntry::make('is_credit')
                                ->label('是否为信用')
                                ->boolean(),
                            TextEntry::make('line_item_type')
                                ->label('行项目类型'),
                            TextEntry::make('title')
                                ->label('标题'),
                            Section::make('本地化内容')
                                ->schema([
                                    TextEntry::make('localized_content.nameForDisplay')
                                        ->label('显示名称'),
                                    TextEntry::make('localized_content.detailForDisplay')
                                        ->label('显示详情'),
                                    TextEntry::make('localized_content.invoiceLine3')
                                        ->label('发票行3'),
                                    ImageEntry::make('localized_content.artworkURL')
                                        ->label('艺术作品')
                                        ->height(100)
                                        ->circular(false)
                                        ->defaultImageUrl(url('/images/default-artwork.png'))
                                        ->extraImgAttributes([
                                            'loading' => 'lazy',
                                            'alt'     => '艺术作品',
                                        ])
                                        ->openUrlInNewTab()
                                        ->alignCenter(),
                                    TextEntry::make('localized_content.supportURL')
                                        ->label('支持URL')
                                        ->url(fn($state) => $state)
                                        ->openUrlInNewTab(),
                                    TextEntry::make('localized_content.mediaType')
                                        ->label('媒体类型'),
                                    TextEntry::make('localized_content.subscriptionCoverageDescription')
                                        ->label('订阅覆盖说明'),
                                    IconEntry::make('localized_content.complete')
                                        ->label('是否完整')
                                        ->boolean(),
                                ])
                                ->columns(2)
                                ->collapsible(),

                            Section::make('订阅信息')
                                ->schema([
                                    TextEntry::make('subscription_info.trunkPricings')
                                        ->label('主要定价')
                                        ->formatStateUsing(function ($state) {
                                            if (empty($state)) {
                                                return null;
                                            }

                                            return collect($state)->map(function ($pricing) {
                                                return "- 价格: {$pricing['price']} {$pricing['currency']}\n".
                                                    "  周期: {$pricing['period']}";
                                            })->join("\n");
                                        })
                                        ->markdown(),
                                    TextEntry::make('subscription_info.branchPricings')
                                        ->label('分支定价')
                                        ->formatStateUsing(function ($state) {
                                            if (empty($state)) {
                                                return null;
                                            }

                                            return collect($state)->map(function ($pricing) {
                                                return "- 价格: {$pricing['price']} {$pricing['currency']}\n".
                                                    "  周期: {$pricing['period']}";
                                            })->join("\n");
                                        })
                                        ->markdown(),
                                    IconEntry::make('subscription_info.isContingentPricingTrunk')
                                        ->label('是否为条件主要定价')
                                        ->boolean(),
                                    IconEntry::make('subscription_info.isContingentPricingBranch')
                                        ->label('是否为条件分支定价')
                                        ->boolean(),
                                    IconEntry::make('subscription_info.shouldDisplayImpactReport')
                                        ->label('是否显示影响报告')
                                        ->boolean(),
                                ])
                                ->columns(2)
                                ->collapsible(),
                        ])
                        ->columns(2),
                ]),
        ]);
    }
}
