<?php

namespace App\Filament\Resources\RequestTransporterResource\Pages;

use App\Filament\Resources\RequestTransporterResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use App\Models\MaterialRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListRequestTransporters extends ListRecords
{
    protected static string $resource = RequestTransporterResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'pendientes' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('materialRequest', fn (Builder $q) =>
                        $q->where('current_status', MaterialRequest::STATUS_PENDING)
                    ))
                ->icon('heroicon-o-clock'),

            'en_proceso' => Tab::make('En Proceso')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('materialRequest', fn (Builder $q) =>
                        $q->where('current_status', MaterialRequest::STATUS_ACCEPTED)
                        ->where('current_transporter_id', Auth::id())
                    ))
                ->icon('heroicon-o-arrow-path'),

            'reprogramados' => Tab::make('Reprogramados')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('materialRequest', fn (Builder $q) =>
                        $q->where('current_status', MaterialRequest::STATUS_RESCHEDULED)
                    ))
                ->icon('heroicon-o-calendar'),

            'finalizados' => Tab::make('Finalizados')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('materialRequest', fn (Builder $q) =>
                        $q->where('current_status', MaterialRequest::STATUS_COMPLETED)
                        ->where('current_transporter_id', Auth::id())
                    ))
                ->icon('heroicon-o-check-circle'),

            'fallidos' => Tab::make('Fallidos')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('materialRequest', fn (Builder $q) =>
                        $q->where('current_status', MaterialRequest::STATUS_FAILED)
                        ->where('current_transporter_id', Auth::id())
                    ))
                ->icon('heroicon-o-x-circle')
        ];
    }
}
