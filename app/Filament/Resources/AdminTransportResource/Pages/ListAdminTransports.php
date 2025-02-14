<?php

namespace App\Filament\Resources\AdminTransportResource\Pages;

use App\Filament\Resources\AdminTransportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\Resources\AdminTransportResource\Widgets;

class ListAdminTransports extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AdminTransportResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\TransportStats::class,
        ];
    }
}
