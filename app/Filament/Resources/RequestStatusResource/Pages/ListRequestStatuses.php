<?php

namespace App\Filament\Resources\RequestStatusResource\Pages;

use App\Filament\Resources\RequestStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestStatuses extends ListRecords
{
    protected static string $resource = RequestStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
