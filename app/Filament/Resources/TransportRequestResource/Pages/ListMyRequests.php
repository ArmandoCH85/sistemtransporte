<?php

namespace App\Filament\Resources\TransportRequestResource\Pages;

use App\Filament\Resources\TransportRequestResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMyRequests extends ListRecords
{
    protected static string $resource = TransportRequestResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->whereHas('currentTransporter', function ($query) {
                $query->where('transporter_id', auth()->id());
            });
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
