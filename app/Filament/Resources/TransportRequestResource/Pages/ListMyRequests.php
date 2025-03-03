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
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;

class ListMyRequests extends ListRecords
{
    use ExposesTableToWidgets;
    use InteractsWithTable;

    protected static string $resource = TransportRequestResource::class;

    protected function getTableQuery(): Builder
    {
        return MaterialRequestTransport::query()
            ->where('requester_id', Auth::id());
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
                ViewAction::make()
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info'),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_PENDING
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar solicitud')
                    ->modalDescription('¿Está seguro que desea eliminar esta solicitud? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('No, cancelar'),

                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_PENDING
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
