<?php

namespace App\Filament\Resources\TransportRequestResource\Pages;

use App\Filament\Resources\TransportRequestResource;
use App\Models\MaterialRequestTransport;
use App\Services\TransporterWorkdayService;
use Carbon\Carbon;
use Filament\Actions\Action as HeaderAction;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\DateTimePicker;

class ListPendingRequests extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransportRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('closeWorkday')
                ->label('Finalizar mi jornada')
                ->icon('heroicon-o-briefcase')
                ->color('success')
                ->visible(fn (): bool => Auth::check() && Auth::user()->hasRole('Transportista'))
                ->requiresConfirmation()
                ->modalHeading('Finalizar jornada laboral')
                ->modalDescription('Se registrará la salida de hoy. Este cierre solo puede hacerse una vez por día.')
                ->modalSubmitActionLabel('Confirmar cierre')
                ->action(function () {
                    $user = Auth::user();

                    if (! $user || ! $user->hasRole('Transportista')) {
                        Notification::make()
                            ->title('Acción no permitida')
                            ->danger()
                            ->send();

                        return;
                    }

                    $result = app(TransporterWorkdayService::class)->closeForToday($user);
                    $log = $result['log'];

                    if ($result['created']) {
                        Notification::make()
                            ->title('Jornada cerrada con éxito')
                            ->body(
                                'Inicio: '
                                . Carbon::parse($log->started_at)->format('d/m/Y H:i')
                                . ' | Salida: '
                                . Carbon::parse($log->ended_at)->format('d/m/Y H:i')
                            )
                            ->success()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Ya registraste tu salida hoy')
                        ->body(
                            'Tu jornada del '
                            . Carbon::parse($log->work_date)->format('d/m/Y')
                            . ' ya fue cerrada a las '
                            . Carbon::parse($log->ended_at)->format('H:i')
                        )
                        ->warning()
                        ->persistent()
                        ->send();
                }),
        ];
    }

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
                    ->form([
                        DateTimePicker::make('scheduled_date')
                            ->label('Nueva fecha programada')
                            ->required()
                            ->minDate(now())
                            ->withoutSeconds()
                            ->native(false)
                            ->placeholder('Seleccione fecha y hora')
                            ->helperText('Elija la nueva fecha y hora para realizar este servicio')
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->action(function (MaterialRequestTransport $record, array $data) {
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_RESCHEDULED,
                            'scheduled_date' => $data['scheduled_date'],
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Solicitud reprogramada correctamente')
                            ->body('La solicitud ha sido reprogramada para el ' . date('d/m/Y H:i', strtotime($data['scheduled_date'])))
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

                ViewAction::make('viewFailed')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_FAILED
                    )
                    ->infolist([
                        InfolistSection::make('Motivo del Fallo')
                            ->icon('heroicon-o-x-circle')
                            ->iconColor('danger')
                            ->schema([
                                TextEntry::make('failureReason')
                                    ->label('Motivo')
                                    ->getStateUsing(function (MaterialRequestTransport $record) {
                                        $transporterData = RequestTransporter::where('request_id', $record->id)
                                            ->where('transporter_id', Auth::id())
                                            ->where('assignment_status', 'accepted')
                                            ->first();
                                        return $transporterData ? $transporterData->comments : 'No se especificó motivo';
                                    }),
                            ]),

                        InfolistSection::make('Detalles de la Solicitud')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('id')
                                            ->label('ID'),
                                        TextEntry::make('created_at')
                                            ->label('Fecha')
                                            ->dateTime('d/m/Y H:i'),
                                        TextEntry::make('requester.name')
                                            ->label('Solicitante'),
                                        TextEntry::make('material_description')
                                            ->label('Material'),
                                        TextEntry::make('pickup_location')
                                            ->label('Origen')
                                            ->formatStateUsing(fn (string $state): string => MaterialRequestTransport::LOCATIONS[$state] ?? $state),
                                        TextEntry::make('delivery_location')
                                            ->label('Destino')
                                            ->formatStateUsing(fn (string $state): string => MaterialRequestTransport::LOCATIONS[$state] ?? $state),
                                    ]),
                            ]),

                        InfolistSection::make('Evidencia Fotográfica')
                            ->schema([
                                ImageEntry::make('failureImage')
                                    ->label('')
                                    ->getStateUsing(function (MaterialRequestTransport $record) {
                                        $image = \DB::table('images')
                                            ->where('request_id', $record->id)
                                            ->where('type', 'delivery')
                                            ->orderBy('created_at', 'desc')
                                            ->first();
                                        return $image ? $image->image_url : null;
                                    })
                                    ->disk('public')
                                    ->visible(fn ($state) => $state !== null),
                            ]),
                    ]),

                Action::make('fail')
                    ->label('No se realizó')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_ACCEPTED
                    )
                    ->form([
                        Textarea::make('failure_reason')
                            ->label('Motivo')
                            ->placeholder('Explique por qué no se pudo realizar el servicio')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),

                        FileUpload::make('failure_image')
                            ->label('Evidencia fotográfica')
                            ->helperText('Adjunte una imagen que evidencie el motivo')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('failure-images')
                            ->visibility('public')
                            ->maxSize(5120) // 5MB
                    ])
                    ->action(function (MaterialRequestTransport $record, array $data) {
                        // 1. Actualizar estado de la solicitud
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_FAILED,
                        ]);

                        // 2. Agregar comentario en request_transporters
                        $transporterRecord = RequestTransporter::where('request_id', $record->id)
                            ->where('transporter_id', Auth::id())
                            ->where('assignment_status', 'accepted')
                            ->first();

                        if ($transporterRecord) {
                            $transporterRecord->update([
                                'comments' => $data['failure_reason']
                            ]);
                        }

                        // 3. Guardar la imagen en la tabla images
                        if (isset($data['failure_image'])) {
                            $imageUrl = $data['failure_image'];

                            // Crear registro en la tabla images
                            \DB::table('images')->insert([
                                'request_id' => $record->id,
                                'type' => 'delivery', // Asumimos que es tipo delivery para la evidencia de fallo
                                'image_url' => $imageUrl,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Servicio marcado como no realizado')
                            ->body('Se ha registrado el motivo y la evidencia fotográfica')
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


