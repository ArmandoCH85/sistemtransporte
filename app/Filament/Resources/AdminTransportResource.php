<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminTransportResource\Pages;
use App\Models\MaterialRequestAdmin;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\FilterGroup;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Tables\Enums\FiltersLayout;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransportRequestsExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class AdminTransportResource extends Resource
{
    protected static ?string $model = MaterialRequestAdmin::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'ADMINISTRACIÓN';
    protected static ?string $navigationLabel = 'Reportes de Administración';
    protected static ?string $modelLabel = 'Reportes de Administración';
    protected static ?string $pluralModelLabel = 'Reportes de Administración';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Solicitante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currentTransporter.transporter.name')
                    ->label('Transportista')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('materialCategory.name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('originArea.name')
                    ->label('Área')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('current_status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'accepted',
                        'info' => 'rescheduled',
                        'success' => 'completed',
                        'danger' => 'failed'
                    ])
                    ->formatStateUsing(fn (string $state): string => MaterialRequestAdmin::getStatuses()[$state] ?? $state)
                    ->label('Estado')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('desde')
                            ->label('Desde'),
                        DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['desde'] ?? null) {
                            $indicators['desde'] = 'Desde ' . Carbon::parse($data['desde'])->format('d/m/Y');
                        }
                        if ($data['hasta'] ?? null) {
                            $indicators['hasta'] = 'Hasta ' . Carbon::parse($data['hasta'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                SelectFilter::make('current_status')
                    ->label('Estado')
                    ->options(MaterialRequestAdmin::getStatuses())
                    ->multiple(),

                SelectFilter::make('material_category_id')
                    ->label('Categoría')
                    ->relationship('materialCategory', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('origin_area_id')
                    ->label('Área')
                    ->relationship('originArea', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('transporter')
                    ->label('Transportista')
                    ->relationship('currentTransporter.transporter', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->actions([
                Action::make('view')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(function (MaterialRequestAdmin $record) {
                        $record->load([
                            'requester',
                            'materialCategory',
                            'originArea',
                            'currentTransporter.transporter',
                            'transporters' => function ($query) {
                                $query->latest('created_at');
                            }
                        ]);
                        return view('transport-requests.detail-modal', [
                            'request' => $record
                        ]);
                    })
                    ->modalWidth('5xl')
                    ->modalHeading('Detalle de Solicitud'),
            ])
            ->bulkActions([
                BulkAction::make('export')
                    ->label('Exportar Seleccionados')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        return Excel::download(
                            new TransportRequestsExport($records),
                            'solicitudes_transporte_' . now()->format('d-m-Y_H-i-s') . '.xlsx',
                            \Maatwebsite\Excel\Excel::XLSX
                        );
                    })
                    ->deselectRecordsAfterCompletion()
            ])
            ->headerActions([
                Action::make('exportAll')
                    ->label('Exportar Todo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        return Excel::download(
                            new TransportRequestsExport(),
                            'todas_solicitudes_transporte_' . now()->format('d-m-Y_H-i-s') . '.xlsx',
                            \Maatwebsite\Excel\Excel::XLSX
                        );
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminTransports::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
