<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialCategoryResource\Pages;
use App\Filament\Resources\MaterialCategoryResource\RelationManagers;
use App\Models\MaterialCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Models\MaterialRequest;

/**
 * Recurso para la gestión de Categorías de Materiales
 * Este recurso permite administrar las diferentes categorías de materiales que pueden ser transportados
 */
class MaterialCategoryResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = MaterialCategory::class;  // Modelo Eloquent asociado al recurso

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';  // Icono mostrado en el menú
    protected static ?string $modelLabel = 'Categoría de Material';          // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Categorías de Materiales'; // Etiqueta plural
    protected static ?string $navigationLabel = 'Categorías';               // Etiqueta en el menú
    protected static ?string $navigationGroup = 'CONFIGURACIÓN';           // Grupo en el menú

    /**
     * Define la estructura del formulario para crear/editar categorías
     * Incluye campos para la información básica de la categoría
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Campo para el nombre de la categoría
                Forms\Components\TextInput::make('name')
                    ->required()                    // Campo obligatorio
                    ->maxLength(255)                // Longitud máxima
                    ->label('Nombre')               // Etiqueta en español
                    ->placeholder('Nombre de la categoría')
                    ->unique(ignorable: fn ($record) => $record) // Asegura nombres únicos
                    ->autofocus(),                 // El cursor se posiciona aquí al cargar

                // Campo para la descripción de la categoría
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()              // Ocupa todo el ancho disponible
                    ->label('Descripción')          // Etiqueta en español
                    ->placeholder('Descripción detallada de la categoría')
                    ->rows(3)                       // Altura del campo en líneas
                    ->helperText('Proporcione una descripción clara de los materiales que pertenecen a esta categoría'),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar categorías
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para el nombre de la categoría
                Tables\Columns\TextColumn::make('name')
                    ->searchable()                  // Permite búsqueda por nombre
                    ->sortable()                    // Permite ordenar por nombre
                    ->label('Nombre')               // Etiqueta en español
                    ->description(fn (MaterialCategory $record): string =>
                        Str::limit($record->description ?? '', 50)), // Muestra descripción truncada con manejo de nulos

                // Columnas de timestamps con toggle para mostrar/ocultar
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()                    // Formato fecha y hora
                    ->sortable()                    // Permite ordenar por fecha
                    ->toggleable(isToggledHiddenByDefault: true) // Oculto por defecto
                    ->label('Fecha de creación'),   // Etiqueta en español

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Última actualización'),
            ])
            ->filters([
                // Filtros para la tabla (se pueden agregar según necesidades)
            ])
            ->actions([
                // Acciones disponibles para cada registro
                Tables\Actions\EditAction::make()    // Botón de edición
                    ->label('Editar')               // Etiqueta en español
                    ->modalHeading('Editar Categoría'), // Título del modal
            ])
            ->bulkActions([
                // Acciones masivas disponibles
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()  // Eliminación masiva
                        ->label('Eliminar seleccionadas')    // Etiqueta en español
                        ->modalHeading('Eliminar Categorías'), // Título del modal
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
            'index' => Pages\ListMaterialCategories::route('/'),           // Página principal - listado
            'create' => Pages\CreateMaterialCategory::route('/create'),    // Página de creación
            'edit' => Pages\EditMaterialCategory::route('/{record}/edit'), // Página de edición
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public function delete(): void
    {
        // Verificar si hay solicitudes que usan esta categoría
        if (MaterialRequest::where('material_category_id', $this->id)->exists()) {
            throw new \Exception('No se puede eliminar esta categoría porque hay solicitudes que la están usando.');
        }

        parent::delete();
    }
}
