<?php

namespace App\Filament\Resources\SecuritySettingResource\Pages;

use App\Filament\Resources\SecuritySettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSecuritySettings extends ListRecords
{
    protected static string $resource = SecuritySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
