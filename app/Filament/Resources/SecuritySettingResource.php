<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecuritySettingResource\Pages\EditSecuritySetting;
use App\Models\SecuritySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SecuritySettingResource extends Resource
{
    protected static ?string $model = SecuritySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = '安全设置';
    protected static ?string $modelLabel = '安全设置';
    protected static ?string $slug = 'security-settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TagsInput::make('authorized_ips')
                    ->label('Authorized IP Addresses')
                    ->placeholder('示例: 1.1.1.1,2.2.2.1-2.2.2.2')
                    ->helperText('设置访问授权IP，可设置多个IP地址，注意：一旦设置授权IP，只有指定IP的电脑能访问'),

                Forms\Components\TagsInput::make('configuration.blacklist_ips')
                    ->label('blacklist IP Addresses')
                    ->placeholder('示例: 1.1.1.1,2.2.2.1-2.2.2.2')
                    ->helperText('设置黑名单 IP，可设置多个IP地址，注意：一旦设置黑名单 IP，则黑名单 IP 的电脑不能访问'),

                Forms\Components\TextInput::make('safe_entrance')
                    ->label('Safe Entrance URL')
                    ->prefix('/')
                    ->required()
                    ->default('admin')
                    ->placeholder('admin')
                    ->helperText('管理入口，设置后只能通过指定安全入口登录,如: /admin')
                    ->alphaDash()
                    ->maxLength(255),

                Forms\Components\TextInput::make('configuration.country_code')
                    ->placeholder('')
                    ->helperText('国家区号，如: CN')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('authorized_ips'),
                Tables\Columns\TextColumn::make('safe_entrance'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSecuritySetting::route('/'),
        ];
    }
}
