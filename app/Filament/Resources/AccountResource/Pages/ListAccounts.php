<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Imports\AccountImporter;
use App\Filament\Resources\AccountResource;
use App\Jobs\ImportCsvJob;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\ImportAction::make()
                ->importer(AccountImporter::class)
                ->job(ImportCsvJob::class),
        ];
    }
}
