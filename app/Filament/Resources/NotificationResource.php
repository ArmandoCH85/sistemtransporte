<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Filament\Resources\NotificationResource\RelationManagers;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

/**
 * Recurso para la gestión de Notificaciones en el panel administrativo
 * Este recurso permite administrar las notificaciones del sistema, incluyendo su creación, visualización y gestión
 */
class NotificationResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = Notification::class;  // Modelo Eloquent asociado al recurso

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-bell';  // Icono de campana en el menú
    protected static ?string $navigationGroup = 'SISTEMA';         // Grupo en el menú de navegación
    protected static ?string $modelLabel = 'Notificación';        // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Notificaciones'; // Etiqueta plural

    /**
     * Define la estructura del formulario para crear/editar notificaciones
     * Incluye campos para el contenido y configuración de la notificación
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Notificación')
                    ->description('Información principal de la notificación')
                    ->schema([
                        // Campo para el título
                        Forms\Components\TextInput::make('title')
                            ->required()                    // Campo obligatorio
                            ->maxLength(255)                // Longitud máxima
                            ->label('Título')               // Etiqueta en español
                            ->placeholder('Título de la notificación')
                            ->columnSpan(2),               // Ocupa 2 columnas

                        // Campo para el tipo de notificación
                        Forms\Components\Select::make('type')
                            ->options([
                                'info' => 'Información',
                                'warning' => 'Advertencia',
                                'success' => 'Éxito',
                                'error' => 'Error'
                            ])
                            ->required()
                            ->label('Tipo')
                            ->default('info'),             // Valor por defecto

                        // Campo para el contenido
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->label('Contenido')
                            ->placeholder('Escriba el contenido de la notificación')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),           // Ocupa todo el ancho

                        // Campo para la fecha de expiración
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de Expiración')
                            ->placeholder('Seleccione fecha y hora')
                            ->helperText('Dejar vacío si no expira'),

                        // Selector de usuarios destinatarios
                        Forms\Components\Select::make('recipients')
                            ->multiple()                   // Selección múltiple
                            ->relationship('recipients', 'name')
                            ->preload()                    // Carga previa
                            ->searchable()                 // Permite búsqueda
                            ->label('Destinatarios')
                            ->helperText('Seleccione los usuarios que recibirán la notificación'),
                    ])->columns(3),                        // 3 columnas para esta sección
            ]);
    }

    /**
     * Define la estructura de la tabla para listar notificaciones
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para el título
                Tables\Columns\TextColumn::make('title')
                    ->searchable()                  // Permite búsqueda
                    ->sortable()                    // Permite ordenamiento
                    ->label('Título')
                    ->limit(50),                    // Limita la longitud mostrada

                // Columna para el tipo (con badges de colores)
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'info',
                        'warning' => 'warning',
                        'success' => 'success',
                        'danger' => 'error',
                    ])
                    ->searchable()
                    ->sortable()
                    ->label('Tipo'),

                // Columna para la fecha de creación
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()                    // Formato fecha y hora
                    ->sortable()
                    ->label('Creada el'),

                // Columna para la fecha de expiración
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Expira el'),

                // Columna para el estado de lectura
                Tables\Columns\IconColumn::make('read_at')
                    ->boolean()                     // Muestra icono booleano
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Leída'),
            ])
            ->filters([
                // Filtro por tipo de notificación
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'info' => 'Información',
                        'warning' => 'Advertencia',
                        'success' => 'Éxito',
                        'error' => 'Error'
                    ])
                    ->label('Tipo'),

                // Filtro por estado de lectura
                Tables\Filters\TernaryFilter::make('read')
                    ->placeholder('Todas')
                    ->trueLabel('Leídas')
                    ->falseLabel('No leídas')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: fn (Builder $query) => $query->whereNull('read_at'),
                    ),
            ])
            ->actions([
                // Acciones disponibles para cada registro
                Tables\Actions\ViewAction::make()     // Ver detalles
                    ->label('Ver'),
                Tables\Actions\EditAction::make()     // Editar notificación
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()   // Eliminar notificación
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                // Acciones masivas
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()  // Eliminación masiva
                        ->label('Eliminar seleccionadas'),
                ]),
            ]);
    }

    /**
     * Define las relaciones disponibles para este recurso
     * Permite gestionar datos relacionados desde la interfaz
     */
    public static function getRelations(): array
    {
        return [
            // Aquí se pueden definir relaciones con otros modelos
            // Por ejemplo, relación con usuarios, eventos, etc.
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),           // Página principal - listado
            'create' => Pages\CreateNotification::route('/create'),    // Página de creación
            'edit' => Pages\EditNotification::route('/{record}/edit'), // Página de edición
        ];
    }

    /**
     * Configura la consulta base para el recurso
     * Filtra las notificaciones según el usuario actual
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereHas('recipients', function ($q) {
                    $q->where('user_id', Auth::id());
                })->orWhereDoesntHave('recipients');
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
