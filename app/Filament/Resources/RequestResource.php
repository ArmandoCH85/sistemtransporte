<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;
use App\Models\Request;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Recurso para la gestión de Solicitudes Base
 * Este recurso maneja las solicitudes base del sistema, proporcionando
 * la estructura fundamental para otros tipos de solicitudes más específicas
 */
class RequestResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = Request::class;  // Modelo Eloquent asociado

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-document-text';  // Icono de documento en el menú
    protected static ?string $navigationGroup = 'SISTEMA';                  // Grupo en el menú de navegación
    protected static ?string $modelLabel = 'Solicitud Base';               // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Solicitudes Base';       // Etiqueta plural

    /**
     * Define la estructura del formulario para crear/editar solicitudes
     * Incluye campos para la información básica de la solicitud
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Solicitud')
                    ->description('Detalles básicos de la solicitud')
                    ->schema([
                        // Solicitante
                        Forms\Components\Select::make('requester_id')
                            ->relationship('requester', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Solicitante')
                            ->helperText('Usuario que realiza la solicitud'),

                        // Categoría del material
                        Forms\Components\Select::make('material_category_id')
                            ->relationship('materialCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Categoría de Material')
                            ->helperText('Tipo de material a transportar'),

                        // Área de origen
                        Forms\Components\Select::make('origin_area_id')
                            ->relationship('originArea', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Área de Origen')
                            ->helperText('Área desde donde se recogerá el material'),
                    ])->columns(3),

                Forms\Components\Section::make('Detalles del Material')
                    ->description('Información específica del material')
                    ->schema([
                        // Descripción del material
                        Forms\Components\Textarea::make('material_description')
                            ->required()
                            ->maxLength(1000)
                            ->label('Descripción del Material')
                            ->placeholder('Describa detalladamente el material')
                            ->helperText('Incluya características importantes como peso, dimensiones, etc.')
                            ->columnSpanFull(),

                        // Comentarios adicionales
                        Forms\Components\Textarea::make('comments')
                            ->maxLength(1000)
                            ->label('Comentarios')
                            ->placeholder('Información adicional relevante')
                            ->helperText('Cualquier detalle adicional importante')
                            ->columnSpanFull(),

                        // Estado de completitud
                        Forms\Components\Toggle::make('fields_completed')
                            ->required()
                            ->label('Campos Completados')
                            ->helperText('Indica si todos los campos requeridos están completos')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('Información de Recogida')
                    ->description('Detalles del punto de recogida')
                    ->schema([
                        // Dirección de recogida
                        Forms\Components\TextInput::make('pickup_address')
                            ->required()
                            ->maxLength(255)
                            ->label('Dirección de Recogida')
                            ->placeholder('Dirección completa del punto de recogida')
                            ->helperText('Incluya referencias para facilitar la ubicación'),

                        // Contacto en punto de recogida
                        Forms\Components\TextInput::make('pickup_contact')
                            ->required()
                            ->maxLength(255)
                            ->label('Contacto de Recogida')
                            ->placeholder('Nombre de la persona de contacto'),

                        // Teléfono de contacto recogida
                        Forms\Components\TextInput::make('pickup_phone')
                            ->required()
                            ->tel()
                            ->maxLength(255)
                            ->label('Teléfono de Recogida')
                            ->placeholder('+34 XXX XXX XXX')
                            ->helperText('Número de contacto en el punto de recogida'),
                    ])->columns(3),

                Forms\Components\Section::make('Información de Entrega')
                    ->description('Detalles del punto de entrega')
                    ->schema([
                        // Dirección de entrega
                        Forms\Components\TextInput::make('delivery_address')
                            ->required()
                            ->maxLength(255)
                            ->label('Dirección de Entrega')
                            ->placeholder('Dirección completa del punto de entrega')
                            ->helperText('Incluya referencias para facilitar la ubicación'),

                        // Contacto en punto de entrega
                        Forms\Components\TextInput::make('delivery_contact')
                            ->required()
                            ->maxLength(255)
                            ->label('Contacto de Entrega')
                            ->placeholder('Nombre de la persona de contacto'),

                        // Teléfono de contacto entrega
                        Forms\Components\TextInput::make('delivery_phone')
                            ->required()
                            ->tel()
                            ->maxLength(255)
                            ->label('Teléfono de Entrega')
                            ->placeholder('+34 XXX XXX XXX')
                            ->helperText('Número de contacto en el punto de entrega'),
                    ])->columns(3),

                Forms\Components\Section::make('Estado de la Solicitud')
                    ->description('Información sobre el estado actual')
                    ->schema([
                        // Estado actual
                        Forms\Components\TextInput::make('current_status')
                            ->required()
                            ->maxLength(255)
                            ->label('Estado Actual')
                            ->default('pending')
                            ->disabled()
                            ->helperText('Estado actual de la solicitud'),

                        // Imagen de evidencia (si existe)
                        Forms\Components\FileUpload::make('evidence_image')
                            ->image()
                            ->label('Imagen de Evidencia')
                            ->directory('evidence-images')
                            ->helperText('Imagen que documenta el estado de la solicitud'),
                    ])->columns(2),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar solicitudes
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID del solicitante
                Tables\Columns\TextColumn::make('requester_id')
                    ->numeric()
                    ->sortable()
                    ->label('Solicitante'),

                // Categoría del material
                Tables\Columns\TextColumn::make('material_category_id')
                    ->numeric()
                    ->sortable()
                    ->label('Categoría'),

                // Área de origen
                Tables\Columns\TextColumn::make('origin_area_id')
                    ->numeric()
                    ->sortable()
                    ->label('Área'),

                // Estado de completitud
                Tables\Columns\IconColumn::make('fields_completed')
                    ->boolean()
                    ->label('Completada'),

                // Dirección de recogida
                Tables\Columns\TextColumn::make('pickup_address')
                    ->searchable()
                    ->label('Dirección Recogida')
                    ->limit(30),

                // Contacto de recogida
                Tables\Columns\TextColumn::make('pickup_contact')
                    ->searchable()
                    ->label('Contacto Recogida'),

                // Teléfono de recogida
                Tables\Columns\TextColumn::make('pickup_phone')
                    ->searchable()
                    ->label('Teléfono Recogida'),

                // Dirección de entrega
                Tables\Columns\TextColumn::make('delivery_address')
                    ->searchable()
                    ->label('Dirección Entrega')
                    ->limit(30),

                // Contacto de entrega
                Tables\Columns\TextColumn::make('delivery_contact')
                    ->searchable()
                    ->label('Contacto Entrega'),

                // Teléfono de entrega
                Tables\Columns\TextColumn::make('delivery_phone')
                    ->searchable()
                    ->label('Teléfono Entrega'),

                // Estado actual
                Tables\Columns\TextColumn::make('current_status')
                    ->searchable()
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                // Fechas importantes
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creada'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizada'),
            ])
            ->filters([
                // Filtros personalizados se pueden agregar aquí
            ])
            ->actions([
                // Acciones disponibles para cada registro
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                // Acciones masivas
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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
            // Por ejemplo, relación con transportistas, estados, imágenes, etc.
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),           // Página principal - listado
            'create' => Pages\CreateRequest::route('/create'),    // Página de creación
            'edit' => Pages\EditRequest::route('/{record}/edit'), // Página de edición
        ];
    }

    /**
     * Controla la visibilidad del recurso en la navegación
     * Este recurso base no se muestra en el menú ya que se utilizan sus extensiones
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;  // No mostrar en el menú de navegación
    }
}
