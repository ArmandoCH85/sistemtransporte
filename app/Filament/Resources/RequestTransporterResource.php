<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestTransporterResource\Pages;
use App\Filament\Resources\RequestTransporterResource\RelationManagers;
use App\Models\RequestTransporter;
use App\Models\MaterialRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords\Tab;

/**
 * Recurso para la gestión de Asignaciones de Transportistas
 * Este recurso maneja la asignación de transportistas a las solicitudes de material,
 * permitiendo el seguimiento del estado de cada asignación y la gestión del proceso
 * de transporte desde la asignación hasta la finalización del servicio.
 */
class RequestTransporterResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = RequestTransporter::class;  // Modelo Eloquent asociado

    // Configuración de navegación
    protected static ?string $navigationIcon = 'heroicon-o-truck';  // Icono en la barra de navegación
    protected static ?string $navigationGroup = 'TRANSPORTISTA';    // Grupo en el menú de navegación
    protected static ?string $navigationLabel = 'Asignaciones de Transporte';  // Etiqueta en el menú
    protected static ?string $modelLabel = 'Asignación de Transporte';        // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Asignaciones de Transporte'; // Etiqueta plural

    /**
     * Define la estructura del formulario para crear/editar asignaciones
     * Incluye campos para la información de la asignación y el transportista
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección principal del formulario
                Section::make('Detalles de la Asignación')
                    ->description('Información principal de la asignación del transportista')
                    ->schema([
                        // Selector de solicitud
                        Select::make('request_id')
                            ->relationship(
                                'materialRequest',
                                'id',
                                fn (Builder $query) => $query->latest()
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Solicitud')
                            ->helperText('Seleccione la solicitud de material a asignar'),

                        // Selector de transportista
                        Select::make('transporter_id')
                            ->relationship(
                                'transporter',
                                'name',
                                fn (Builder $query) => $query->where('is_active', true)
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Transportista')
                            ->helperText('Seleccione el transportista disponible para esta solicitud'),

                        // Estado de la asignación
                        Select::make('assignment_status')
                            ->options([
                                RequestTransporter::STATUS_PENDING => 'Pendiente de Aceptación',
                                RequestTransporter::STATUS_ACCEPTED => 'Aceptado por Transportista',
                                RequestTransporter::STATUS_REJECTED => 'Rechazado/Reprogramado',
                                RequestTransporter::STATUS_COMPLETED => 'Servicio Completado',
                            ])
                            ->required()
                            ->label('Estado de Asignación')
                            ->helperText('Estado actual de la asignación del transportista'),
                    ])->columns(3),

                // Sección de fechas y tiempos
                Section::make('Control de Tiempos')
                    ->description('Registro de fechas importantes en el proceso')
                    ->schema([
                        // Fecha de asignación
                        DateTimePicker::make('assignment_date')
                            ->required()
                            ->label('Fecha de Asignación')
                            ->helperText('Momento en que se realizó la asignación inicial')
                            ->timezone('America/Lima'),

                        // Fecha de respuesta
                        DateTimePicker::make('response_date')
                            ->label('Fecha de Respuesta')
                            ->helperText('Momento en que el transportista respondió a la asignación')
                            ->timezone('America/Lima'),

                        // Fecha estimada de servicio
                        DateTimePicker::make('estimated_date')
                            ->label('Fecha Estimada')
                            ->helperText('Fecha estimada para realizar el servicio')
                            ->timezone('America/Lima'),
                    ])->columns(3),

                // Sección de comentarios y evidencias
                Section::make('Comentarios y Evidencias')
                    ->description('Información adicional y documentación del servicio')
                    ->schema([
                        // Comentarios generales
                        Textarea::make('comments')
                            ->maxLength(1000)
                            ->label('Comentarios')
                            ->placeholder('Comentarios sobre la asignación o el servicio')
                            ->helperText('Incluya cualquier información relevante sobre la asignación')
                            ->columnSpanFull(),

                        // Evidencia fotográfica
                        FileUpload::make('evidence_images')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->directory('transport-evidence')
                            ->label('Evidencias Fotográficas')
                            ->helperText('Puede subir hasta 5 imágenes como evidencia del servicio')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar asignaciones
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('materialRequest.id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('materialRequest.current_status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => MaterialRequest::STATUS_PENDING,
                        'primary' => MaterialRequest::STATUS_ACCEPTED,
                        'danger' => MaterialRequest::STATUS_RESCHEDULED,
                        'success' => MaterialRequest::STATUS_COMPLETED,
                        'danger' => MaterialRequest::STATUS_FAILED,
                    ]),
                TextColumn::make('materialRequest.material_description')
                    ->limit(30)
                    ->searchable()
                    ->label('Material')
                    ->tooltip(fn ($record): string => $record->materialRequest->material_description),

                // Información de ubicaciones
                TextColumn::make('materialRequest.pickup_address')
                    ->limit(30)
                    ->label('Origen')
                    ->tooltip(fn ($record): string =>
                        "Contacto: {$record->materialRequest->pickup_contact}\n" .
                        "Teléfono: {$record->materialRequest->pickup_phone}\n" .
                        "Dirección: {$record->materialRequest->pickup_address}"
                    ),

                TextColumn::make('materialRequest.delivery_address')
                    ->limit(30)
                    ->label('Destino')
                    ->tooltip(fn ($record): string =>
                        "Contacto: {$record->materialRequest->delivery_contact}\n" .
                        "Teléfono: {$record->materialRequest->delivery_phone}\n" .
                        "Dirección: {$record->materialRequest->delivery_address}"
                    ),

                // Fechas importantes
                TextColumn::make('assignment_date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Asignado'),

                TextColumn::make('response_date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Respondido')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por estado
                Tables\Filters\SelectFilter::make('current_status')
                    ->options(MaterialRequest::getStatuses())
                    ->label('Estado')
                    ->placeholder('Todos los estados'),

                // Filtro por fecha
                Tables\Filters\Filter::make('assignment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('assignment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('assignment_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                // Acción para marcar como no realizado
                Action::make('fail')
                    ->label('No se realizó')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('failure_reason')
                            ->label('Motivo')
                            ->required()
                            ->placeholder('Explique el motivo por el que no se pudo realizar'),
                        FileUpload::make('evidence_image')
                            ->label('Foto de evidencia')
                            ->image()
                            ->disk('public')
                            ->directory('evidence-images')
                            ->required()
                    ])
                    ->visible(fn ($record) => $record->materialRequest->current_status === MaterialRequest::STATUS_ACCEPTED)
                    ->action(function ($record, array $data) {
                        $record->materialRequest->update([
                            'current_status' => MaterialRequest::STATUS_FAILED,
                            'evidence_image' => $data['evidence_image']
                        ]);
                        $record->update([
                            'comments' => $data['failure_reason']
                        ]);
                    }),

                // Acción para aceptar solicitud
                Action::make('accept')
                    ->label('Aceptar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => 
                        $record->materialRequest->current_status === MaterialRequest::STATUS_PENDING ||
                        $record->materialRequest->current_status === MaterialRequest::STATUS_RESCHEDULED
                    )
                    ->action(function ($record) {
                        $record->materialRequest->update([
                            'current_status' => MaterialRequest::STATUS_ACCEPTED,
                            'current_transporter_id' => Auth::user()->id
                        ]);
                        $record->update([
                            'assignment_status' => RequestTransporter::STATUS_ACCEPTED,
                            'assignment_date' => now(),
                            'response_date' => now(),
                        ]);
                    }),

                // Acción para finalizar servicio
                Action::make('complete')
                    ->label('Finalizar Servicio')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->materialRequest->current_status === MaterialRequest::STATUS_ACCEPTED)
                    ->action(function ($record) {
                        $record->materialRequest->update(['current_status' => MaterialRequest::STATUS_COMPLETED]);
                        $record->update(['assignment_status' => RequestTransporter::STATUS_COMPLETED]);
                    }),

                // Acción para reprogramar
                Action::make('reschedule')
                    ->label('Reprogramar')
                    ->icon('heroicon-o-calendar')
                    ->requiresConfirmation()
                    ->modalHeading('Reprogramar Solicitud')
                    ->modalDescription('¿Está seguro de que desea reprogramar esta solicitud? La solicitud volverá a estado pendiente.')
                    ->action(function ($record) {
                        // Obtenemos la solicitud de material
                        $materialRequest = $record->materialRequest;
                        $materialRequest->reschedule();
                        Notification::make()
                            ->title('Solicitud reprogramada')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => 
                        $record->transporter_id === Auth::user()->id && 
                        $record->materialRequest->current_status === MaterialRequest::STATUS_ACCEPTED
                    ),
            ])
            ->defaultSort('assignment_date', 'desc');
    }

    /**
     * Define las relaciones disponibles para este recurso
     * Permite gestionar datos relacionados desde la interfaz
     */
    public static function getRelations(): array
    {
        return [
            // Aquí se pueden definir relaciones con otros modelos
            // Por ejemplo, relación con imágenes, estados, etc.
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestTransporters::route('/'),      // Página principal - listado
            'create' => Pages\CreateRequestTransporter::route('/create'), // Página de creación
            'edit' => Pages\EditRequestTransporter::route('/{record}/edit'), // Página de edición
        ];
    }

    /**
     * Configura la consulta base para el recurso
     * Filtra las solicitudes según su estado actual y el transportista asignado
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Asegurarnos de que siempre cargamos la relación materialRequest
        return $query->with('materialRequest');
    }

    /**
     * Controla la visibilidad del recurso en la navegación
     * Este recurso no se muestra en el menú ya que se accede desde otras vistas
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;  // No mostrar en el menú de navegación
    }
}
