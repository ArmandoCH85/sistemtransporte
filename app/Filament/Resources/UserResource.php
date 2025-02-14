<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

/**
 * Recurso para la gestión de Usuarios en el panel administrativo
 * Este recurso permite administrar los usuarios del sistema, incluyendo sus roles y permisos
 */
class UserResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = User::class;  // Modelo Eloquent asociado al recurso

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-users';  // Icono mostrado en el menú de navegación
    protected static ?string $navigationGroup = 'ADMINISTRACIÓN';   // Grupo en el menú de navegación
    protected static ?string $modelLabel = 'Usuario';              // Etiqueta singular para el recurso
    protected static ?string $pluralModelLabel = 'Usuarios';       // Etiqueta plural para el recurso

    /**
     * Define la estructura del formulario para crear/editar usuarios
     * Incluye campos para la información personal y credenciales
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->description('Datos básicos del usuario')
                    ->schema([
                        // Campo para el nombre completo
                        Forms\Components\TextInput::make('name')
                            ->required()                    // Campo obligatorio
                            ->maxLength(255)                // Longitud máxima
                            ->label('Nombre Completo')      // Etiqueta en español
                            ->placeholder('Juan Pérez')     // Ejemplo de nombre
                            ->autofocus(),                 // Foco automático

                        // Campo para el correo electrónico
                        Forms\Components\TextInput::make('email')
                            ->email()                      // Validación de formato email
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn ($record) => $record) // Email único
                            ->label('Correo Electrónico')
                            ->placeholder('usuario@dominio.com'),

                        // Campo para el teléfono
                        Forms\Components\TextInput::make('phone')
                            ->tel()                        // Campo tipo teléfono
                            ->maxLength(20)
                            ->label('Teléfono')
                            ->placeholder('+34 XXX XXX XXX'),
                    ])->columns(3),                        // 3 columnas para esta sección

                Forms\Components\Section::make('Credenciales')
                    ->description('Contraseña y configuración de acceso')
                    ->schema([
                        // Campo para la contraseña
                        Forms\Components\TextInput::make('password')
                            ->password()                   // Campo tipo contraseña
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->label('Contraseña')
                            ->placeholder('••••••••'),

                        // Campo para confirmar contraseña
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->label('Confirmar Contraseña')
                            ->placeholder('••••••••')
                            ->required(fn (string $context): bool => $context === 'create')
                            ->same('password')             // Debe coincidir con password
                            ->dehydrated(false),           // No se guarda en BD
                    ])->columns(2),                        // 2 columnas para esta sección

                Forms\Components\Section::make('Roles y Permisos')
                    ->description('Configuración de accesos y privilegios')
                    ->schema([
                        // Selector de roles
                        Forms\Components\Select::make('roles')
                            ->multiple()                   // Permite selección múltiple
                            ->relationship('roles', 'name')
                            ->preload()                    // Carga previa de opciones
                            ->searchable()                 // Permite búsqueda
                            ->label('Roles')
                            ->helperText('Seleccione uno o más roles para el usuario'),
                    ]),
            ]);
    }

    /**
     * Define la estructura de la tabla para listar usuarios
     * Configura las columnas, filtros y acciones disponibles
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para el nombre
                Tables\Columns\TextColumn::make('name')
                    ->searchable()                  // Permite búsqueda por nombre
                    ->sortable()                    // Permite ordenar por nombre
                    ->label('Nombre'),              // Etiqueta en español

                // Columna para el email
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('Correo Electrónico'),

                // Columna para el teléfono
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('Teléfono'),

                // Columna para los roles (con badges)
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()                       // Muestra como badges
                    ->color('primary')              // Color de los badges
                    ->searchable()
                    ->sortable()
                    ->label('Roles'),

                // Columnas de timestamps
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creado el'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizado el'),
            ])
            ->filters([
                // Filtros para la tabla (se pueden agregar según necesidades)
            ])
            ->actions([
                // Acciones disponibles para cada registro
                Tables\Actions\EditAction::make()    // Botón de edición
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()  // Botón de eliminación
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                // Acciones masivas
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()  // Eliminación masiva
                        ->label('Eliminar seleccionados'),
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
            // Por ejemplo, relación con solicitudes, roles, etc.
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),           // Página principal - listado
            'create' => Pages\CreateUser::route('/create'),    // Página de creación
            'edit' => Pages\EditUser::route('/{record}/edit'), // Página de edición
        ];
    }

    /**
     * Define los permisos necesarios para acceder a este recurso
     * Implementa la lógica de autorización
     */
    public static function getNavigationGroup(): ?string
    {
        if (!auth()->check()) {
            return null;
        }
        return auth()->user()->hasRole('admin') ? 'ADMINISTRACIÓN' : null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
