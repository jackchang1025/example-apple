<?php

namespace App\Filament\Resources\ProxyConfigurationResource\Pages;

use App\Filament\Resources\ProxyConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyConfiguration extends EditRecord
{
    protected static string $resource = ProxyConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
