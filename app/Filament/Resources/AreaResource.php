<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Filament\Resources\AreaResource\RelationManagers;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Recurso para la gestión de Áreas en el panel administrativo
 * Este recurso permite administrar las diferentes áreas o departamentos de la organización
 */
class AreaResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = Area::class;  // Modelo Eloquent asociado al recurso

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';  // Icono mostrado en el menú de navegación
    protected static ?string $modelLabel = 'Área';                           // Etiqueta singular para el recurso
    protected static ?string $pluralModelLabel = 'Áreas';                    // Etiqueta plural para el recurso

    /**
     * Define la estructura del formulario para crear/editar áreas
     * Incluye campos para la información básica del área
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Campo para el nombre del área
                Forms\Components\TextInput::make('name')
                    ->required()                    // Campo obligatorio
                    ->maxLength(255)                // Longitud máxima de 255 caracteres
                    ->label('Nombre')               // Etiqueta en español
                    ->placeholder('Nombre del área') // Texto de ayuda
                    ->unique(ignorable: fn ($record) => $record) // Asegura nombres únicos
                    ->autofocus(),                 // El cursor se posiciona aquí al cargar

                // Campo para la descripción del área
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()              // Ocupa todo el ancho disponible
                    ->label('Descripción')          // Etiqueta en español
                    ->placeholder('Descripción detallada del área')
                    ->rows(3),                      // Altura del campo en líneas

                // Interruptor para activar/desactivar el área
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('¿Activa?')             // Etiqueta en español
                    ->helperText('Determina si el área está actualmente operativa')
                    ->default(true),                // Valor por defecto: activa

                // Campo para el código único del área
                Forms\Components\TextInput::make('code')
                    ->maxLength(255)
                    ->label('Código')               // Etiqueta en español
                    ->placeholder('Código identificativo del área')
                    ->unique(ignorable: fn ($record) => $record) // Asegura códigos únicos
                    ->helperText('Código único para identificar el área (opcional)'),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar áreas
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para el nombre del área
                Tables\Columns\TextColumn::make('name')
                    ->searchable()                  // Permite búsqueda por nombre
                    ->sortable()                    // Permite ordenar por nombre
                    ->label('Nombre')               // Etiqueta en español
                    ->description(fn (Area $record): string => $record->code ?? ''), // Muestra el código como descripción

                // Columna para el estado activo/inactivo
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()                     // Muestra un icono de check/cross
                    ->sortable()                    // Permite ordenar por estado
                    ->label('Activa')               // Etiqueta en español
                    ->trueIcon('heroicon-o-check-circle')    // Icono para estado activo
                    ->falseIcon('heroicon-o-x-circle'),     // Icono para estado inactivo

                // Columna para el código del área
                Tables\Columns\TextColumn::make('code')
                    ->searchable()                  // Permite búsqueda por código
                    ->sortable()                    // Permite ordenar por código
                    ->label('Código'),              // Etiqueta en español

                // Columnas de timestamps con toggle para mostrar/ocultar
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()                    // Formato fecha y hora
                    ->sortable()                    // Permite ordenar por fecha
                    ->toggleable(isToggledHiddenByDefault: true) // Oculto por defecto
                    ->label('Creada el'),           // Etiqueta en español

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizada el'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Eliminada el'),
            ])
            ->filters([
                // Filtros para la tabla (se pueden agregar según necesidades)
            ])
            ->actions([
                // Acciones disponibles para cada registro
                Tables\Actions\EditAction::make()    // Botón de edición
                    ->label('Editar'),              // Etiqueta en español
            ])
            ->bulkActions([
                // Acciones masivas disponibles
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()  // Eliminación masiva
                        ->label('Eliminar seleccionados'),   // Etiqueta en español
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
            // Por ejemplo, relación con solicitudes de material
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreas::route('/'),           // Página principal - listado
            'create' => Pages\CreateArea::route('/create'),    // Página de creación
            'edit' => Pages\EditArea::route('/{record}/edit'), // Página de edición
        ];
    }
}
