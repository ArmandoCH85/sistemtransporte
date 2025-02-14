<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestStatusResource\Pages;
use App\Filament\Resources\RequestStatusResource\RelationManagers;
use App\Models\RequestStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Recurso para la gestión de Estados de Solicitudes
 * Este recurso maneja el historial y seguimiento de los estados de las solicitudes de transporte,
 * permitiendo un control detallado de los cambios de estado y su evolución
 */
class RequestStatusResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = RequestStatus::class;  // Modelo Eloquent asociado

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-clock-rewind';  // Icono de historial en el menú
    protected static ?string $navigationGroup = 'SISTEMA';                 // Grupo en el menú de navegación
    protected static ?string $modelLabel = 'Estado de Solicitud';         // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Estados de Solicitudes'; // Etiqueta plural

    /**
     * Define la estructura del formulario para crear/editar estados
     * Incluye campos para registrar los cambios de estado y sus detalles
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Estado')
                    ->description('Información sobre el cambio de estado de la solicitud')
                    ->schema([
                        // Solicitud relacionada
                        Forms\Components\Select::make('request_id')
                            ->relationship('request', 'id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Solicitud')
                            ->helperText('Solicitud a la que pertenece este estado'),

                        // Estado anterior
                        Forms\Components\TextInput::make('previous_status')
                            ->maxLength(255)
                            ->label('Estado Anterior')
                            ->placeholder('Estado previo al cambio')
                            ->helperText('Dejar vacío si es el estado inicial'),

                        // Nuevo estado
                        Forms\Components\Select::make('new_status')
                            ->required()
                            ->options([
                                'PENDIENTE' => 'Pendiente',
                                'ACEPTADO' => 'Aceptado',
                                'REPROGRAMADO' => 'Reprogramado',
                                'FINALIZADO' => 'Finalizado',
                                'FALLIDA' => 'Fallida',
                            ])
                            ->label('Nuevo Estado')
                            ->helperText('Estado al que cambia la solicitud'),

                        // Comentarios sobre el cambio
                        Forms\Components\Textarea::make('comments')
                            ->maxLength(1000)
                            ->label('Comentarios')
                            ->placeholder('Razón o detalles del cambio de estado')
                            ->helperText('Información adicional sobre el cambio de estado')
                            ->columnSpanFull(),

                        // Usuario que realiza el cambio
                        Forms\Components\Select::make('changed_by')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Cambiado por')
                            ->helperText('Usuario que realizó el cambio de estado'),
                    ])->columns(3),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar estados
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID de la solicitud
                Tables\Columns\TextColumn::make('request_id')
                    ->numeric()
                    ->sortable()
                    ->label('Solicitud'),

                // Estado anterior
                Tables\Columns\TextColumn::make('previous_status')
                    ->searchable()
                    ->label('Estado Anterior')
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? RequestStatus::getStatusLabel($state) : 'Estado Inicial'),

                // Nuevo estado (con badges de colores)
                Tables\Columns\BadgeColumn::make('new_status')
                    ->colors([
                        'warning' => 'PENDIENTE',
                        'success' => 'ACEPTADO',
                        'info' => 'REPROGRAMADO',
                        'success' => 'FINALIZADO',
                        'danger' => 'FALLIDA',
                    ])
                    ->searchable()
                    ->label('Nuevo Estado')
                    ->formatStateUsing(fn (string $state): string =>
                        RequestStatus::getStatusLabel($state)),

                // Usuario que realizó el cambio
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Cambiado por'),

                // Fecha del cambio
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Fecha del Cambio'),
            ])
            ->filters([
                // Filtro por estado
                Tables\Filters\SelectFilter::make('new_status')
                    ->options([
                        'PENDIENTE' => 'Pendiente',
                        'ACEPTADO' => 'Aceptado',
                        'REPROGRAMADO' => 'Reprogramado',
                        'FINALIZADO' => 'Finalizado',
                        'FALLIDA' => 'Fallida',
                    ])
                    ->label('Estado'),

                // Filtro por usuario
                Tables\Filters\SelectFilter::make('changed_by')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Usuario'),
            ])
            ->actions([
                // Ver detalles
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->modalHeading('Detalles del Cambio de Estado'),
            ])
            ->bulkActions([
                // Acciones masivas
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');  // Ordenar por fecha de creación descendente
    }

    /**
     * Define las relaciones disponibles para este recurso
     * Permite gestionar datos relacionados desde la interfaz
     */
    public static function getRelations(): array
    {
        return [
            // Aquí se pueden definir relaciones con otros modelos
            // Por ejemplo, relación con solicitudes, usuarios, etc.
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestStatuses::route('/'),           // Página principal - listado
            'create' => Pages\CreateRequestStatus::route('/create'),    // Página de creación
            'edit' => Pages\EditRequestStatus::route('/{record}/edit'), // Página de edición
        ];
    }

    /**
     * Controla la visibilidad del recurso en la navegación
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;  // No mostrar en el menú de navegación
    }
}
