<?php

namespace App\Filament\Resources;


use App\Filament\Resources\PhoneResource\Pages;
use App\Models\Phone;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class PhoneResource extends Resource
{
    protected static ?string $model = Phone::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected function getHeaderActions(): array
    {
        return [

            CreateAction::make(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                PhoneInput::make('phone')
                    ->required()
                    ->unique(ignorable: fn (?Model $record): ?Model => $record)
                    ->helperText('请选择国际区号并输入电话号码')
                    ->displayNumberFormat(PhoneInputNumberType::E164)
//                    ->displayNumberFormat(PhoneInputNumberType::NATIONAL)
//                    ->countryStatePath('country_code')
                    ->defaultCountry('US'),

                Forms\Components\TextInput::make('phone_address')
                    ->required()
                    ->url()
                    ->prefix('https://')
                    ->helperText('请输入有效的URL地址'),

                Forms\Components\Select::make('status')
                    ->options([
                        'normal' => '正常',
                        'invalid' => '失效',
                        'bound' => '已绑定'
                    ])
                    ->default('normal')
                    ->required(),

                Forms\Components\TextInput::make('country_code')->default('')->readOnly(),
                Forms\Components\TextInput::make('country_dial_code')->default('')->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                PhoneColumn::make('phone')->displayFormat(PhoneInputNumberType::E164),//->countryColumn('country_code')
//                Tables\Columns\TextColumn::make('phone'),
//                Tables\Columns\TextColumn::make('phone_address'),
                Tables\Columns\TextColumn::make('country_code'),
//                Tables\Columns\TextColumn::make('country_dial_code'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhones::route('/'),
            'create' => Pages\CreatePhone::route('/create'),
            'edit' => Pages\EditPhone::route('/{record}/edit'),
        ];
    }
}
