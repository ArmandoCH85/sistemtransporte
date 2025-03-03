<?php

namespace App\Filament\Resources\TransportRequestResource\Pages;

use App\Filament\Resources\TransportRequestResource;
use App\Models\MaterialRequestTransport;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Notifications\Notification;
use App\Models\RequestTransporter;

class ListPendingRequests extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransportRequestResource::class;

    public function getTabs(): array
    {
        return [
            'pendientes' => Tab::make()
                ->label('Pendientes')
                ->icon('heroicon-o-clock')
                ->badge(MaterialRequestTransport::whereIn('current_status', [
                    MaterialRequestTransport::STATUS_PENDING,
                    MaterialRequestTransport::STATUS_RESCHEDULED
                ])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('current_status', [
                    MaterialRequestTransport::STATUS_PENDING,
                    MaterialRequestTransport::STATUS_RESCHEDULED
                ])),

            'en_proceso' => Tab::make()
                ->label('En Proceso')
                ->icon('heroicon-o-truck')
                ->badge(MaterialRequestTransport::where('current_status', MaterialRequestTransport::STATUS_ACCEPTED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted');
                    })->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('current_status', MaterialRequestTransport::STATUS_ACCEPTED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted');
                    })),

            'finalizados' => Tab::make()
                ->label('Finalizados')
                ->icon('heroicon-o-check-circle')
                ->badge(MaterialRequestTransport::where('current_status', MaterialRequestTransport::STATUS_COMPLETED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted');
                    })->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('current_status', MaterialRequestTransport::STATUS_COMPLETED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted');
                    })),

            'fallidos' => Tab::make()
                ->label('Fallidos')
                ->icon('heroicon-o-x-circle')
                ->badge(MaterialRequestTransport::where('current_status', MaterialRequestTransport::STATUS_FAILED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted');
                    })->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('current_status', MaterialRequestTransport::STATUS_FAILED)
                    ->whereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted');
                    })),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Fecha'),

                TextColumn::make('material_description')
                    ->limit(30)
                    ->searchable()
                    ->label('Material'),

                TextColumn::make('pickup_location')
                    ->formatStateUsing(fn (string $state): string => MaterialRequestTransport::LOCATIONS[$state] ?? 'No especificado')
                    ->label('Origen'),

                TextColumn::make('delivery_location')
                    ->formatStateUsing(fn (string $state): string => MaterialRequestTransport::LOCATIONS[$state] ?? 'No especificado')
                    ->label('Destino'),

                TextColumn::make('current_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rescheduled' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => MaterialRequestTransport::getStatuses()[$state] ?? $state)
                    ->label('Estado'),
            ])
            ->actions([
                Action::make('accept')
                    ->label('Aceptar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        in_array($record->current_status, [
                            MaterialRequestTransport::STATUS_PENDING,
                            MaterialRequestTransport::STATUS_RESCHEDULED
                        ])
                    )
                    ->action(function (MaterialRequestTransport $record) {
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_ACCEPTED,
                        ]);

                        RequestTransporter::create([
                            'request_id' => $record->id,
                            'transporter_id' => Auth::id(),
                            'assignment_status' => 'accepted',
                            'assignment_date' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Solicitud aceptada correctamente')
                            ->send();

                        $this->refreshList();
                    }),

                Action::make('reschedule')
                    ->label('Reprogramar')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        in_array($record->current_status, [
                            MaterialRequestTransport::STATUS_PENDING,
                            MaterialRequestTransport::STATUS_RESCHEDULED
                        ])
                    )
                    ->action(function (MaterialRequestTransport $record) {
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_RESCHEDULED
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Solicitud reprogramada correctamente')
                            ->send();

                        $this->refreshList();
                    }),

                ViewAction::make()
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_ACCEPTED
                    ),

                Action::make('complete')
                    ->label('Finalizar Servicio')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_ACCEPTED
                    )
                    ->action(function (MaterialRequestTransport $record) {
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_COMPLETED,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Servicio finalizado correctamente')
                            ->send();

                        $this->refreshList();
                    }),

                Action::make('fail')
                    ->label('No se realizó')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_ACCEPTED
                    )
                    ->action(function (MaterialRequestTransport $record) {
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_FAILED,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Servicio marcado como no realizado')
                            ->send();

                        $this->refreshList();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s');
    }

    public function refreshList(): void
    {
        $this->dispatch('refresh');
    }
}
