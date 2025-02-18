<?php

namespace App\Filament\Resources\TransportRequestResource\Pages;

use App\Filament\Resources\TransportRequestResource;
use App\Models\MaterialRequestTransport;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPendingRequests extends ListRecords
{
    protected static string $resource = TransportRequestResource::class;

    protected function getTableQuery(): Builder
    {
        // Anulamos el filtro global del Resource
        return MaterialRequestTransport::query();
    }

    public function getTabs(): array
    {
        return [
            'pendientes' => Tab::make('Pendientes')
                ->icon('heroicon-o-clock')
                ->badge(
                    MaterialRequestTransport::whereIn('current_status', [
                        MaterialRequestTransport::STATUS_PENDING,
                        MaterialRequestTransport::STATUS_RESCHEDULED
                    ])->count()
                )
                ->query(fn (Builder $query) => $query->whereIn('current_status', [
                    MaterialRequestTransport::STATUS_PENDING,
                    MaterialRequestTransport::STATUS_RESCHEDULED
                ])),

            'en_proceso' => Tab::make('En Proceso')
                ->icon('heroicon-o-truck')
                ->badge(
                    MaterialRequestTransport::where('current_status', MaterialRequestTransport::STATUS_ACCEPTED)
                        ->whereHas('currentTransporter', function ($query) {
                            $query->where('transporter_id', Auth::id());
                        })->count()
                )
                ->query(fn (Builder $query) => $query->where('current_status', MaterialRequestTransport::STATUS_ACCEPTED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id());
                    })),

            'finalizados' => Tab::make('Finalizados')
                ->icon('heroicon-o-check-circle')
                ->badge(
                    MaterialRequestTransport::where('current_status', MaterialRequestTransport::STATUS_COMPLETED)
                        ->whereHas('currentTransporter', function ($query) {
                            $query->where('transporter_id', Auth::id());
                        })->count()
                )
                ->query(fn (Builder $query) => $query->where('current_status', MaterialRequestTransport::STATUS_COMPLETED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id());
                    })),

            'fallidos' => Tab::make('Fallidos')
                ->icon('heroicon-o-x-circle')
                ->badge(
                    MaterialRequestTransport::where('current_status', MaterialRequestTransport::STATUS_FAILED)
                        ->whereHas('currentTransporter', function ($query) {
                            $query->where('transporter_id', Auth::id());
                        })->count()
                )
                ->query(fn (Builder $query) => $query->where('current_status', MaterialRequestTransport::STATUS_FAILED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id());
                    })),
        ];
    }
}
