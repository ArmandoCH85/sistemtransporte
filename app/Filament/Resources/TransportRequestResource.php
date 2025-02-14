<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransportRequestResource\Pages;
use App\Models\MaterialRequestTransport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Tabs\Tab;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Tabs\Tabs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use App\Models\RequestTransporter;
use App\Models\User;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;

/**
 * Recurso para la gestión de Solicitudes de Transporte
 * Este recurso maneja las solicitudes de transporte realizadas por los usuarios,
 * permitiendo su creación, seguimiento y gestión
 */
class TransportRequestResource extends Resource
{
    /**
     * Configuración básica del recurso
     * Define el modelo base y las opciones de navegación
     */
    protected static ?string $model = MaterialRequestTransport::class;

    // Configuración de la navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-truck';  // Icono de camión en el menú
    protected static ?string $navigationGroup = 'TRANSPORTISTA';      // Grupo en el menú de navegación
    protected static ?string $navigationLabel = 'Solicitudes de Transportes';      // Etiqueta en el menú
    protected static ?string $modelLabel = 'Solicitud de Transportes';            // Etiqueta singular
    protected static ?string $pluralModelLabel = 'Solicitudes de Transportes';    // Etiqueta plural

    /**
     * Define la estructura del formulario para crear/editar solicitudes
     * Incluye campos para la información del transporte y detalles de la solicitud
     */


     protected static function getPermissionPrefixName(): string
     {
         return 'transport_request';
     }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Solicitud')
                    ->description('Detalles básicos de la solicitud de transporte')
                    ->schema([
                        // Solicitante (usuario actual)
                        Select::make('requester_id')
                            ->relationship('requester', 'name')
                            ->default(Auth::id())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->label('Solicitante')
                            ->helperText('Usuario que realiza la solicitud'),

                        // Área de origen
                        Select::make('origin_area_id')
                            ->relationship('originArea', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Área de Origen')
                            ->helperText('Área desde donde se recogerá el material'),

                        // Tipo de material
                        Select::make('material_category_id')
                            ->relationship('materialCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Tipo de Material')
                            ->helperText('Categoría del material a transportar'),
                    ])->columns(3),

                Section::make('Detalles del Material')
                    ->description('Información específica sobre el material a transportar')
                    ->schema([
                        // Descripción del material
                        Textarea::make('material_description')
                            ->required()
                            ->maxLength(1000)
                            ->label('Descripción del Material')
                            ->placeholder('Describa detalladamente el material a transportar')
                            ->helperText('Incluya características importantes como peso, dimensiones, etc.')
                            ->rows(3)
                            ->columnSpanFull(),

                        // Comentarios adicionales
                        Textarea::make('comments')
                            ->maxLength(1000)
                            ->label('Comentarios')
                            ->placeholder('Información adicional relevante')
                            ->helperText('Cualquier detalle adicional que deba ser considerado')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Información de Recogida')
                    ->description('Detalles del punto de recogida')
                    ->schema([
                        // Dirección de recogida
                        Forms\Components\Textarea::make('pickup_address')
                            ->required()
                            ->maxLength(1000)
                            ->label('Dirección')
                            ->placeholder('Dirección completa del punto de recogida')
                            ->helperText('Incluya referencias para facilitar la ubicación'),

                        // Contacto en punto de recogida
                        Forms\Components\TextInput::make('pickup_contact')
                            ->required()
                            ->maxLength(255)
                            ->label('Contacto')
                            ->placeholder('Nombre de la persona de contacto'),

                        // Teléfono de contacto
                        Forms\Components\TextInput::make('pickup_phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->label('Teléfono')
                            ->placeholder('+51 XXX XXX XXX')
                            ->helperText('Número de teléfono del contacto en el punto de recogida')
                            ->regex('/^\+?[0-9]{1,4}[-. ]?[0-9]{6,14}$/'),
                    ])->columns(3),

                Section::make('Información de Entrega')
                    ->description('Detalles del punto de entrega')
                    ->schema([
                        // Dirección de entrega
                        Forms\Components\Textarea::make('delivery_address')
                            ->required()
                            ->maxLength(1000)
                            ->label('Dirección')
                            ->placeholder('Dirección completa del punto de entrega')
                            ->helperText('Incluya referencias para facilitar la ubicación'),

                        // Contacto en punto de entrega
                        Forms\Components\TextInput::make('delivery_contact')
                            ->required()
                            ->maxLength(255)
                            ->label('Contacto')
                            ->placeholder('Nombre de la persona de contacto'),

                        // Teléfono de contacto
                        Forms\Components\TextInput::make('delivery_phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->label('Teléfono')
                            ->placeholder('+51 XXX XXX XXX')
                            ->helperText('Número de teléfono del contacto en el punto de entrega')
                            ->regex('/^\+?[0-9]{1,4}[-. ]?[0-9]{6,14}$/'),
                    ])->columns(3),
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
            ->paginated([
                'defaultPerPage' => 10,
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (! request()->routeIs('*.transport-requests.index')) {
                    $query->where(function ($query) {
                        $query->where('current_status', MaterialRequestTransport::STATUS_PENDING)
                            ->orWhere(function ($query) {
                                $query->where('current_status', MaterialRequestTransport::STATUS_ACCEPTED)
                                    ->whereHas('currentTransporter', function ($query) {
                                        $query->where('transporter_id', Auth::id());
                                    });
                            })
                            ->orWhere(function ($query) {
                                $query->where('current_status', MaterialRequestTransport::STATUS_COMPLETED)
                                    ->whereHas('currentTransporter', function ($query) {
                                        $query->where('transporter_id', Auth::id());
                                    });
                            })
                            ->orWhere(function ($query) {
                                $query->where('current_status', MaterialRequestTransport::STATUS_FAILED)
                                    ->whereHas('currentTransporter', function ($query) {
                                        $query->where('transporter_id', Auth::id());
                                    });
                            });
                    });
                }
                return $query;
            })
            ->actions([
                Action::make('view')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(function (MaterialRequestTransport $record) {
                        $record->load([
                            'requester',
                            'materialCategory',
                            'originArea',
                            'currentTransporter.transporter',
                            'transporters' => function ($query) {
                                $query->latest('created_at');
                            }
                        ]);
                        return view('transport-requests.detail-modal', [
                            'request' => $record
                        ]);
                    })
                    ->modalWidth('5xl')
                    ->modalHeading('Detalle de Solicitud de Transporte'),

                Action::make('accept')
                    ->label('Aceptar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->current_status === MaterialRequestTransport::STATUS_PENDING)
                    ->action(function (MaterialRequestTransport $record) {
                        // 1. Crear la asignación del transportista
                        $record->transporters()->create([
                            'transporter_id' => Auth::id(),
                            'assignment_status' => 'accepted',
                            'assignment_date' => now(),
                            'response_date' => now(),
                        ]);

                        // 2. Actualizar el estado de la solicitud
                        $record->update(['current_status' => MaterialRequestTransport::STATUS_ACCEPTED]);

                        // 3. Obtener el nombre del transportista que aceptó la solicitud
                        $transportistaNombre = Auth::user()->name;

                        // 4. Enviar correo al usuario que creó la solicitud
                        $usuario = User::find($record->requester_id);
                        if ($usuario) {
                            $details = [
                                'title' => 'Solicitud de Material Aceptada',
                                'content' => "Su solicitud ha sido aceptada por el transportista {$transportistaNombre}. Pronto se pondrá en contacto con usted.",
                            ];
                            Mail::to($usuario->email)->send(new TestMail($details));
                        }

                        // 5. Enviar correo a todos los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material Asignada',
                                'content' => "La solicitud #{$record->id} ha sido aceptada por el transportista {$transportistaNombre}.",
                            ];
                            Mail::to($admin->email)->send(new TestMail($details));
                        }
                    }),

                Action::make('reschedule')
                    ->label('Reprogramar')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->form([
                        DateTimePicker::make('new_date')
                            ->label('Nueva Fecha')
                            ->required(),
                        Textarea::make('comments')
                            ->label('Comentarios')
                            ->required(),
                    ])
                    ->visible(fn ($record) => $record->current_status === MaterialRequestTransport::STATUS_PENDING)
                    ->action(function (MaterialRequestTransport $record, array $data) {
                        // 1. Actualizar el estado y datos de la solicitud
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_RESCHEDULED,
                            'rescheduled_date' => $data['new_date'],
                            'reschedule_comments' => $data['comments']
                        ]);

                        // 2. Crear registro de transportista
                        $record->transporters()->create([
                            'transporter_id' => Auth::id(),
                            'assignment_status' => RequestTransporter::STATUS_REJECTED,
                            'assignment_date' => now(),
                            'response_date' => now(),
                            'comments' => $data['comments']
                        ]);

                        // 3. Obtener el nombre del transportista y formatear la fecha
                        $transportistaNombre = Auth::user()->name;
                        $nuevaFecha = \Carbon\Carbon::parse($data['new_date'])->format('d/m/Y H:i');

                        // 4. Enviar correo al usuario que creó la solicitud
                        // Obtenemos el usuario a través de la relación requester
                        $usuario = $record->requester;
                        if ($usuario) {
                            Log::info('Enviando correo al usuario: ' . $usuario->email); // Log para debug
                            $details = [
                                'title' => 'Solicitud de Material Reprogramada',
                                'content' => "Su solicitud ha sido reprogramada por el transportista {$transportistaNombre} para la fecha {$nuevaFecha}.\n\nMotivo: {$data['comments']}",
                            ];
                            Mail::to($usuario->email)->send(new TestMail($details));
                        } else {
                            Log::error('No se encontró el usuario para la solicitud: ' . $record->id); // Log para debug
                        }

                        // 5. Enviar correo a todos los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material Reprogramada',
                                'content' => "La solicitud #{$record->id} ha sido reprogramada por el transportista {$transportistaNombre} para la fecha {$nuevaFecha}.\n\nMotivo: {$data['comments']}",
                            ];
                            Mail::to($admin->email)->send(new TestMail($details));
                        }
                    }),

                Action::make('complete')
                    ->label('Finalizar Servicio')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->current_status === MaterialRequestTransport::STATUS_ACCEPTED &&
                        $record->currentTransporter?->transporter_id === Auth::id()
                    )
                    ->action(function (MaterialRequestTransport $record) {
                        // 1. Actualizar el estado de la solicitud
                        $record->update(['current_status' => MaterialRequestTransport::STATUS_COMPLETED]);

                        // 2. Obtener el nombre del transportista que completó el servicio
                        $transportistaNombre = Auth::user()->name;

                        // 3. Enviar correo al usuario que creó la solicitud
                        $usuario = $record->requester;
                        if ($usuario) {
                            Log::info('Enviando correo de finalización al usuario: ' . $usuario->email);
                            $details = [
                                'title' => 'Solicitud de Material Completada',
                                'content' => "Su solicitud ha sido completada exitosamente por el transportista {$transportistaNombre}. El servicio de transporte ha finalizado.",
                            ];
                            Mail::to($usuario->email)->send(new TestMail($details));
                        }

                        // 4. Enviar correo a todos los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material Finalizada',
                                'content' => "La solicitud #{$record->id} ha sido completada exitosamente por el transportista {$transportistaNombre}.",
                            ];
                            Mail::to($admin->email)->send(new TestMail($details));
                        }
                    }),

                Action::make('fail')
                    ->label('No se realizó')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('failure_reason')
                            ->label('Motivo')
                            ->required()
                            ->maxLength(1000)
                            ->placeholder('Explique el motivo por el que no se pudo realizar el servicio')
                            ->helperText('Proporcione detalles específicos sobre por qué no se pudo completar el servicio'),

                        FileUpload::make('evidence_image')
                            ->label('Foto de Evidencia')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('evidence-images')
                            ->maxSize(5120) // 5MB máximo
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->helperText('Suba una foto que evidencie la razón por la que no se pudo realizar el servicio (máx. 5MB)')
                    ])
                    ->visible(fn ($record) =>
                        $record->current_status === MaterialRequestTransport::STATUS_ACCEPTED &&
                        $record->currentTransporter?->transporter_id === Auth::id()
                    )
                    ->action(function ($record, array $data) {
                        // 1. Actualizar el estado y datos de la solicitud
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_FAILED,
                            'evidence_image' => $data['evidence_image'],
                            'comments' => $data['failure_reason']
                        ]);

                        // 2. Obtener el nombre del transportista
                        $transportistaNombre = Auth::user()->name;

                        // 3. Enviar correo al usuario que creó la solicitud
                        $usuario = $record->requester;
                        if ($usuario) {
                            $details = [
                                'title' => 'Solicitud de Material No Realizada',
                                'content' => "Su solicitud no pudo ser realizada por el transportista {$transportistaNombre}.\n\nMotivo: {$data['failure_reason']}",
                            ];
                            Mail::to($usuario->email)->send(new TestMail($details));
                        }

                        // 4. Enviar correo a todos los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material No Realizada',
                                'content' => "La solicitud #{$record->id} no pudo ser realizada por el transportista {$transportistaNombre}.\n\nMotivo: {$data['failure_reason']}",
                            ];
                            Mail::to($admin->email)->send(new TestMail($details));
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Define las relaciones disponibles para este recurso
     * Permite gestionar datos relacionados desde la interfaz
     */
    public static function getRelations(): array
    {
        return [
            // Aquí se pueden definir relaciones con otros modelos
            // Por ejemplo, relación con transportistas, estados, etc.
        ];
    }

    /**
     * Define las páginas disponibles para este recurso
     * Configura las rutas y componentes para listar, crear y editar
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingRequests::route('/'),
            'my-requests' => Pages\ListMyRequests::route('/my-requests'),
        ];
    }

    /**
     * Configura la consulta base para el recurso
     * Filtra las solicitudes según el usuario actual
     */
    public static function getEloquentQuery(): Builder
    {
        if (request()->routeIs('*.transport-requests.index')) {
            return parent::getEloquentQuery()
                ->where('requester_id', Auth::id());
        }

        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('current_status', MaterialRequestTransport::STATUS_PENDING)
                    ->orWhereHas('currentTransporter', function ($query) {
                        $query->where('transporter_id', Auth::id());
                    });
            });
    }
}
