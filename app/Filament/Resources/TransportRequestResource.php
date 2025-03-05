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
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

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

                        // Cambiamos Área de Origen por Área Solicitante
                        Select::make('origin_area_id')
                            ->relationship(
                                'originArea',
                                'name',
                                fn ($query) => $query->active()
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Área Solicitante')
                            ->helperText('Indique el área a la que pertenece la persona que solicita'),

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
                            ->placeholder('Especificar número de cajas, bultos, refrigerados, sobres, tamaño, otros')
                            ->helperText('Detalle la cantidad y tipo de items a transportar')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Información de Recogida')
                    ->description('Detalles del punto de recogida')
                    ->schema([
                        // Dirección de recogida
                        Select::make('pickup_location')
                            ->options([
                                'surco' => 'Surco',
                                'san_isidro' => 'San Isidro',
                                'san_borja_hospitalaria' => 'San Borja Hospitalaria',
                                'lima_ambulatoria' => 'Lima Ambulatoria',
                                'lima_hospitalaria' => 'Lima Hospitalaria',
                                'la_molina' => 'La Molina',
                                'alto_caral' => 'Alto Caral',
                                'san_borja_ambulatoria' => 'San Borja Ambulatoria',
                            ])
                            ->required()
                            ->label('Ubicación')
                            ->placeholder('Seleccione la ubicación'),

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
                    ])->columns(2),

                Section::make('Información de Entrega')
                    ->description('Detalles del punto de entrega')
                    ->schema([
                        // Dirección de entrega
                        Select::make('delivery_location')
                            ->options([
                                'surco' => 'Surco',
                                'san_isidro' => 'San Isidro',
                                'san_borja_hospitalaria' => 'San Borja Hospitalaria',
                                'lima_ambulatoria' => 'Lima Ambulatoria',
                                'lima_hospitalaria' => 'Lima Hospitalaria',
                                'la_molina' => 'La Molina',
                                'alto_caral' => 'Alto Caral',
                                'san_borja_ambulatoria' => 'San Borja Ambulatoria',
                            ])
                            ->required()
                            ->label('Ubicación')
                            ->placeholder('Seleccione la ubicación'),

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
                        $query->whereIn('current_status', [
                            MaterialRequestTransport::STATUS_PENDING,
                            MaterialRequestTransport::STATUS_RESCHEDULED
                        ])
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
                    ->visible(fn ($record) =>
                        $record->current_status === MaterialRequestTransport::STATUS_PENDING ||
                        $record->current_status === MaterialRequestTransport::STATUS_RESCHEDULED
                    )
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
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">✅ Solicitud #'.$record->id.' Aceptada</h2>

                                    <p style="color: #4a5568; margin-bottom: 20px;">
                                        Su solicitud ha sido aceptada y será atendida por un transportista.
                                    </p>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">🚚 Datos del Transportista</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Nombre:</strong> '.Auth::user()->name.'<br>
                                            </p>
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">📦 Detalles de la Solicitud</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Material:</strong> '.$record->material_description.'<br>
                                                <strong>Origen:</strong> '.MaterialRequestTransport::LOCATIONS[$record->pickup_location].' - '.$record->pickup_address.'<br>
                                                <strong>Destino:</strong> '.MaterialRequestTransport::LOCATIONS[$record->delivery_location].' - '.$record->delivery_address.'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($usuario, $details, $record) {
                                $message->to($usuario->email)
                                        ->subject("Solicitud #{$record->id} - Aceptada");
                            });
                        }

                        // 5. Enviar correo a todos los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material Asignada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">👥 Supervisión - Solicitud #'.$record->id.' Asignada</h2>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">🚚 Asignación</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Fecha de Asignación:</strong> '.now()->format('d/m/Y H:i').'
                                            </p>
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">📦 Detalles</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Material:</strong> '.$record->material_description.'<br>
                                                <strong>Origen:</strong> '.MaterialRequestTransport::LOCATIONS[$record->pickup_location].' - '.$record->pickup_address.'<br>
                                                <strong>Destino:</strong> '.MaterialRequestTransport::LOCATIONS[$record->delivery_location].' - '.$record->delivery_address.'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($admin, $details, $record) {
                                $message->to($admin->email)
                                        ->subject("Solicitud #{$record->id} - Asignada a Transportista");
                            });
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
                    ->visible(fn ($record) =>
                        $record->current_status === MaterialRequestTransport::STATUS_PENDING ||
                        $record->current_status === MaterialRequestTransport::STATUS_RESCHEDULED &&
                         $record->currentTransporter?->transporter_id === Auth::id()
                    )
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
                            $details = [
                                'title' => 'Solicitud de Material Reprogramada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">🗓️ Solicitud #'.$record->id.' Reprogramada</h2>

                                    <p style="color: #4a5568; margin-bottom: 20px;">
                                        Su solicitud ha sido reprogramada por el siguiente motivo:
                                    </p>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">⏰ Nueva Programación</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Nueva Fecha:</strong> '.\Carbon\Carbon::parse($data['new_date'])->format('d/m/Y H:i').'<br>
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Motivo:</strong> '.$data['comments'].'
                                            </p>
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">📦 Detalles de la Solicitud</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Material:</strong> '.$record->material_description.'<br>
                                                <strong>Origen:</strong> '.MaterialRequestTransport::LOCATIONS[$record->pickup_location].' - '.$record->pickup_address.'<br>
                                                <strong>Destino:</strong> '.MaterialRequestTransport::LOCATIONS[$record->delivery_location].' - '.$record->delivery_address.'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($usuario, $details, $record) {
                                $message->to($usuario->email)
                                        ->subject("Solicitud #{$record->id} - Reprogramada");
                            });
                        }

                        // 5. Enviar correo a todos los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material Reprogramada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">👥 Supervisión - Solicitud #'.$record->id.' Reprogramada</h2>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">🗓️ Detalles de Reprogramación</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Nueva Fecha:</strong> '.\Carbon\Carbon::parse($data['new_date'])->format('d/m/Y H:i').'<br>
                                                <strong>Motivo:</strong> '.$data['comments'].'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($admin, $details, $record) {
                                $message->to($admin->email)
                                        ->subject("Solicitud #{$record->id} - Reprogramada");
                            });
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
                        $record->update(['current_status' => MaterialRequestTransport::STATUS_COMPLETED]);

                        // Enviar correo al usuario
                        $usuario = $record->requester;
                        if ($usuario) {
                            $details = [
                                'title' => 'Solicitud de Material Completada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">✅ Solicitud #'.$record->id.' Completada</h2>

                                    <p style="color: #4a5568; margin-bottom: 20px;">
                                        Su solicitud ha sido completada exitosamente.
                                    </p>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">🚚 Datos del Servicio</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Fecha de Finalización:</strong> '.now()->format('d/m/Y H:i').'
                                            </p>
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">📦 Detalles del Envío</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Material:</strong> '.$record->material_description.'<br>
                                                <strong>Origen:</strong> '.MaterialRequestTransport::LOCATIONS[$record->pickup_location].' - '.$record->pickup_address.'<br>
                                                <strong>Destino:</strong> '.MaterialRequestTransport::LOCATIONS[$record->delivery_location].' - '.$record->delivery_address.'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($usuario, $details, $record) {
                                $message->to($usuario->email)
                                        ->subject("Solicitud #{$record->id} - Completada");
                            });
                        }

                        // Enviar correo a los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material Finalizada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">👥 Supervisión - Solicitud #'.$record->id.' Completada</h2>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">✅ Detalles de Finalización</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Fecha de Finalización:</strong> '.now()->format('d/m/Y H:i').'<br>
                                                <strong>Estado:</strong> Completado
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($admin, $details, $record) {
                                $message->to($admin->email)
                                        ->subject("Solicitud #{$record->id} - Completada");
                            });
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
                        $record->update([
                            'current_status' => MaterialRequestTransport::STATUS_FAILED,
                            'evidence_image' => $data['evidence_image'],
                            'comments' => $data['failure_reason']
                        ]);

                        // Enviar correo al usuario
                        $usuario = $record->requester;
                        if ($usuario) {
                            $details = [
                                'title' => 'Solicitud de Material No Realizada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">❌ Solicitud #'.$record->id.' No Realizada</h2>

                                    <p style="color: #4a5568; margin-bottom: 20px;">
                                        Lamentamos informarle que su solicitud no pudo ser realizada.
                                    </p>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">⚠️ Motivo</h3>
                                            <p style="margin-left: 20px; color: #4a5568; background-color: #fff5f5; padding: 10px; border-radius: 5px;">
                                                '.$data['failure_reason'].'
                                            </p>
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">📦 Detalles de la Solicitud</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Material:</strong> '.$record->material_description.'<br>
                                                <strong>Origen:</strong> '.MaterialRequestTransport::LOCATIONS[$record->pickup_location].' - '.$record->pickup_address.'<br>
                                                <strong>Destino:</strong> '.MaterialRequestTransport::LOCATIONS[$record->delivery_location].' - '.$record->delivery_address.'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($usuario, $details, $record) {
                                $message->to($usuario->email)
                                        ->subject("Solicitud #{$record->id} - No Realizada");
                            });
                        }

                        // Enviar correo a los super_admin
                        $superAdmins = User::role('super_admin')->get();
                        foreach ($superAdmins as $admin) {
                            $details = [
                                'title' => 'Solicitud de Material No Realizada',
                                'content' => '
                                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                                    <h2 style="color: #2d3748; margin-bottom: 20px;">👥 Supervisión - Solicitud #'.$record->id.' No Realizada</h2>

                                    <div style="background-color: white; padding: 20px; border-radius: 8px;">
                                        <div style="margin-top: 20px;">
                                            <h3 style="color: #2d3748; margin-bottom: 10px;">❌ Detalles del Fallo</h3>
                                            <p style="margin-left: 20px; color: #4a5568;">
                                                <strong>Transportista:</strong> '.Auth::user()->name.'<br>
                                                <strong>Fecha:</strong> '.now()->format('d/m/Y H:i').'<br>
                                                <strong>Motivo:</strong> '.$data['failure_reason'].'
                                            </p>
                                        </div>
                                    </div>

                                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                                        Este es un correo automático, por favor no responder directamente.
                                    </p>
                                </div>
                                ',
                            ];
                            Mail::send('emails.test', ['data' => $details], function($message) use ($admin, $details, $record) {
                                $message->to($admin->email)
                                        ->subject("Solicitud #{$record->id} - No Realizada");
                            });
                        }
                    }),

                // Agregar acción de eliminar
                DeleteAction::make()
                    ->visible(fn (MaterialRequestTransport $record): bool =>
                        $record->current_status === MaterialRequestTransport::STATUS_PENDING &&
                        $record->requester_id === Auth::id()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar solicitud')
                    ->modalDescription('¿Está seguro que desea eliminar esta solicitud? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('No, cancelar'),
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
                $query->where(function ($q) {
                    // Mostrar todas las solicitudes pendientes y reprogramadas
                    $q->whereIn('current_status', [
                        MaterialRequestTransport::STATUS_PENDING,
                        MaterialRequestTransport::STATUS_RESCHEDULED
                    ]);
                })->orWhere(function ($q) {
                    // Mostrar solo las solicitudes asignadas al transportista actual
                    $q->whereIn('current_status', [
                        MaterialRequestTransport::STATUS_ACCEPTED,
                        MaterialRequestTransport::STATUS_COMPLETED,
                        MaterialRequestTransport::STATUS_FAILED
                    ])->whereHas('currentTransporter', function ($q) {
                        $q->where('transporter_id', Auth::id());
                    });
                });
            });
    }

    // Agregar método para controlar la capacidad de eliminar
    public static function canDelete(Model $record): bool
    {
        return $record->current_status === MaterialRequestTransport::STATUS_PENDING &&
               $record->requester_id === Auth::id();
    }
}
