<?php

namespace App\Filament\Resources\AdminTransportResource\Widgets;

use App\Models\MaterialRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransportStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRequests = MaterialRequest::count();
        $completedRequests = MaterialRequest::where('current_status', MaterialRequest::STATUS_COMPLETED)->count();
        $pendingRequests = MaterialRequest::where('current_status', MaterialRequest::STATUS_PENDING)->count();
        $failedRequests = MaterialRequest::where('current_status', MaterialRequest::STATUS_FAILED)->count();

        // Calcular el tiempo promedio de servicio para solicitudes completadas
        $avgServiceTime = MaterialRequest::where('current_status', MaterialRequest::STATUS_COMPLETED)
            ->join('request_transporters', 'requests.id', '=', 'request_transporters.request_id')
            ->whereNotNull('request_transporters.assignment_date')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, request_transporters.assignment_date, requests.updated_at)) as avg_time'))
            ->first()
            ->avg_time ?? 0;

        // Calcular la tasa de éxito
        $successRate = $totalRequests > 0 ? round(($completedRequests / $totalRequests) * 100, 2) : 0;

        return [
            Stat::make('Total Solicitudes', $totalRequests)
                ->description('Todas las solicitudes registradas')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Solicitudes Completadas', $completedRequests)
                ->description($successRate . '% tasa de éxito')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Solicitudes Pendientes', $pendingRequests)
                ->description('Esperando asignación')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Solicitudes Fallidas', $failedRequests)
                ->description('No se pudieron completar')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Tiempo Promedio', round($avgServiceTime, 1) . ' horas')
                ->description('Tiempo promedio de servicio')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
