<?php

namespace App\Filament\Resources\ProxyConfigurationResource\Pages;

use App\Filament\Resources\ProxyConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyConfigurations extends ListRecords
{
    protected static string $resource = ProxyConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
