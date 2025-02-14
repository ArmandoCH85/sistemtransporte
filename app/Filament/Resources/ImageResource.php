<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImageResource\Pages;
use App\Filament\Resources\ImageResource\RelationManagers;
use App\Models\Image;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Recurso para la gestión de Imágenes
 * Este recurso maneja las imágenes asociadas a las solicitudes de transporte,
 * permitiendo su carga, visualización y gestión
 */
class ImageResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = Image::class;  // Modelo Eloquent asociado

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-photo';  // Icono de imagen en el menú
    protected static ?string $navigationGroup = 'SISTEMA';          // Grupo en el menú de navegación
    protected static ?string $modelLabel = 'Imagen';               // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Imágenes';       // Etiqueta plural

    /**
     * Define la estructura del formulario para crear/editar imágenes
     * Incluye campos para la carga y configuración de imágenes
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Imagen')
                    ->description('Información y configuración de la imagen')
                    ->schema([
                        // Solicitud relacionada
                        Forms\Components\Select::make('request_id')
                            ->relationship('request', 'id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Solicitud')
                            ->helperText('Solicitud a la que pertenece esta imagen'),

                        // Tipo de imagen
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'pickup' => 'Recogida',
                                'delivery' => 'Entrega',
                                'evidence' => 'Evidencia',
                                'other' => 'Otro'
                            ])
                            ->label('Tipo')
                            ->helperText('Propósito o categoría de la imagen'),

                        // Campo de carga de imagen
                        Forms\Components\FileUpload::make('image_url')
                            ->image()                      // Acepta solo imágenes
                            ->required()
                            ->maxSize(5120)               // Tamaño máximo: 5MB
                            ->directory('request-images')  // Directorio de almacenamiento
                            ->label('Imagen')
                            ->helperText('Formatos permitidos: jpg, png, gif. Máximo 5MB')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar imágenes
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID de la solicitud relacionada
                Tables\Columns\TextColumn::make('request_id')
                    ->numeric()
                    ->sortable()
                    ->label('Solicitud'),

                // Tipo de imagen
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pickup' => 'Recogida',
                        'delivery' => 'Entrega',
                        'evidence' => 'Evidencia',
                        'other' => 'Otro',
                        default => $state,
                    })
                    ->label('Tipo'),

                // Previsualización de la imagen
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Imagen')
                    ->square()                    // Formato cuadrado
                    ->size(100),                  // Tamaño de la miniatura

                // Fecha de creación
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creada el'),

                // Fecha de actualización
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizada el'),
            ])
            ->filters([
                // Filtro por tipo de imagen
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'pickup' => 'Recogida',
                        'delivery' => 'Entrega',
                        'evidence' => 'Evidencia',
                        'other' => 'Otro'
                    ])
                    ->label('Tipo'),
            ])
            ->actions([
                // Ver imagen
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                // Editar imagen
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
            'index' => Pages\ListImages::route('/'),           // Página principal - listado
            'create' => Pages\CreateImage::route('/create'),    // Página de creación
            'edit' => Pages\EditImage::route('/{record}/edit'), // Página de edición
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
