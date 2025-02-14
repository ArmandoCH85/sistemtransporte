<?php

namespace App\Filament\Resources\RequestTransporterResource\Pages;

use App\Filament\Resources\RequestTransporterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestTransporters extends ListRecords
{
    protected static string $resource = RequestTransporterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
