<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialRequestResource\Pages;
use App\Models\MaterialRequest;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;

class MaterialRequestResource extends Resource
{
    // Configuración básica del recurso
    protected static ?string $model = MaterialRequest::class;  // Modelo asociado al recurso

    // Configuración de navegación
    protected static ?string $navigationIcon = 'heroicon-o-truck';  // Icono en la barra de navegación
    protected static ?string $navigationGroup = 'USUARIOS';         // Grupo en el menú de navegación
    protected static ?string $navigationLabel = 'Solicitud';      // Etiqueta en el menú
    protected static ?string $modelLabel = 'Solicitud';            // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Solicitudes';    // Etiqueta plural


    //prefijo personal
    protected static function getPermissionPrefixName(): string
    {
        return 'material_request';
    }

    // Formulario de creación/edición
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección principal del formulario
                Section::make()
                    ->schema([
                        // Grid de 3 columnas para los campos principales
                        Grid::make(3)
                            ->schema([
                                // Campo de selección de solicitante
                                Select::make('requester_id')
                                    ->relationship('requester', 'name')
                                    ->default(Auth::id())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->label('Solicitante'),

                                // Campo de selección de categoría de material
                                Select::make('material_category_id')
                                    ->relationship('materialCategory', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->label('Categoría de Material'),

                                // Campo de texto para el área de origen
                                TextInput::make('origin_area_id')
                                    ->required()
                                    ->label('Área de Origen')
                                    ->placeholder('Ingrese el área de origen')
                                    ->maxLength(255)
                                    ->helperText('Ingrese el nombre del área desde donde se recogerá el material.'),
                            ]),

                        // Campos de descripción y comentarios
                        Textarea::make('material_description')
                            ->required()
                            ->maxLength(1000)
                            ->label('Descripción del Material')
                            ->placeholder('Describa detalladamente el material a transportar')
                            ->helperText('Incluya características importantes como peso, dimensiones, etc.')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('comments')
                            ->maxLength(1000)
                            ->label('Comentarios')
                            ->placeholder('Especificar número de cajas, bultos, refrigerados, sobres, tamaño, otros')
                            ->helperText('Detalle la cantidad y tipo de items a transportar')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // Sección de información de recogida
                Section::make('Información de Recogida')
                    ->description('Detalles del punto de recogida')
                    ->schema([
                        Select::make('pickup_location')
                            ->options([
                                'surco' => 'Surco',
                                'san_isidro' => 'San Isidro',
                                'san_borja_hospitalaria' => 'San Borja Hospitalaria',
                                'lima_ambulatoria' => 'Lima Ambulatoria',
                                'lima_hospitalaria' => 'Lima Hospitalaria',
                                'la_molina' => 'La Molina',
                            ])
                            ->required()
                            ->label('Ubicación')
                            ->placeholder('Seleccione la ubicación'),

                        Textarea::make('pickup_address')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3)
                            ->label('Dirección')
                            ->helperText('Incluye referencias para facilitar la ubicación'),

                        TextInput::make('pickup_contact')
                            ->required()
                            ->maxLength(255)
                            ->label('Contacto')
                            ->placeholder('Nombre de la persona de contacto'),

                        TextInput::make('pickup_phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->label('Teléfono')
                            ->placeholder('+51 XXX XXX XXX')
                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/'),
                    ])->columns(2),

                // Sección de información de entrega
                Section::make('Información de Entrega')
                    ->description('Detalles del punto de entrega')
                    ->schema([
                        Select::make('delivery_location')
                            ->options([
                                'surco' => 'Surco',
                                'san_isidro' => 'San Isidro',
                                'san_borja_hospitalaria' => 'San Borja Hospitalaria',
                                'lima_ambulatoria' => 'Lima Ambulatoria',
                                'lima_hospitalaria' => 'Lima Hospitalaria',
                                'la_molina' => 'La Molina',
                            ])
                            ->required()
                            ->label('Ubicación')
                            ->placeholder('Seleccione la ubicación'),

                        Textarea::make('delivery_address')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3)
                            ->label('Dirección')
                            ->helperText('Incluye referencias para facilitar la ubicación'),

                        TextInput::make('delivery_contact')
                            ->required()
                            ->maxLength(255)
                            ->label('Contacto')
                            ->placeholder('Nombre de la persona de contacto'),

                        TextInput::make('delivery_phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->label('Teléfono')
                            ->placeholder('+51 XXX XXX XXX')
                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/'),
                    ])->columns(2),

                Section::make('Evidencia Fotográfica')
                    ->description('Foto de las cajas o items a transportar')
                    ->schema([
                        FileUpload::make('package_image')
                            ->image()
                            ->disk('public')
                            ->directory('package-images')
                            ->maxSize(5120)
                            ->label('Foto de las cajas')
                            ->helperText('Si envías una o más cajas debes adjuntar una foto')
                            ->columnSpanFull(),
                    ]),
            ])->statePath('data');
    }

    // Configuración de la tabla de listado
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columnas de la tabla
                TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                TextColumn::make('requester.name')
                    ->searchable()
                    ->sortable()
                    ->label('Solicitante'),

                TextColumn::make('materialCategory.name')
                    ->searchable()
                    ->sortable()
                    ->label('Categoría'),

                TextColumn::make('originArea.name')
                    ->searchable()
                    ->sortable()
                    ->label('Área'),

                // Columna de estado con badges de colores
                TextColumn::make('current_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rescheduled' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => MaterialRequest::getStatuses()[$state] ?? $state)
                    ->searchable()
                    ->sortable()
                    ->label('Estado'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Fecha'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info'),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (MaterialRequest $record): bool => 
                        $record->current_status === MaterialRequest::STATUS_PENDING &&
                        $record->requester_id === Auth::id()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar solicitud')
                    ->modalDescription('¿Está seguro que desea eliminar esta solicitud? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('No, cancelar')
                    ->after(fn () => Notification::make()->success()->title('Solicitud eliminada')->send()),

                Tables\Actions\EditAction::make()
                    ->visible(fn (MaterialRequest $record): bool => 
                        $record->current_status === MaterialRequest::STATUS_PENDING &&
                        $record->requester_id === Auth::id()
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Aquí se pueden agregar filtros para la tabla
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => Auth::check() && Auth::user()->roles->pluck('name')->contains('super_admin')),
            ]);
    }

    // Definición de relaciones disponibles en el recurso
    public static function getRelations(): array
    {
        return [
            // Aquí se pueden definir las relaciones con otros modelos
        ];
    }

    // Definición de las páginas del recurso
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialRequests::route('/'),      // Página de listado
            'create' => Pages\CreateMaterialRequest::route('/create'), // Página de creación
            'edit' => Pages\EditMaterialRequest::route('/{record}/edit'), // Página de edición
        ];
    }

    // Configuración de la consulta base
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->latest();

        if (!Auth::check() || !Auth::user()->roles->pluck('name')->contains('super_admin')) {
            $query->where('requester_id', Auth::id());
        }

        return $query;
    }

    public static function canEdit(Model $record): bool
    {
        // Solo permitir editar si está pendiente
        return $record->current_status === MaterialRequest::STATUS_PENDING;
    }

    public static function canDelete(Model $record): bool
    {
        // Permitir eliminar si:
        // 1. El usuario es el creador de la solicitud Y está pendiente
        // 2. O si es super_admin
        return ($record->requester_id === Auth::id() && $record->current_status === MaterialRequest::STATUS_PENDING) ||
               (Auth::check() && Auth::user()->roles->pluck('name')->contains('super_admin'));
    }
}
