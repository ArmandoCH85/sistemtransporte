<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Notifications\MaterialRequestStatusNotification;

class MaterialRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'requests';

    // Definimos las constantes de estado
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Después de las constantes de estado
    public const LOCATIONS = [
        'surco' => 'Surco',
        'san_isidro' => 'San Isidro',
        'san_borja_hospitalaria' => 'San Borja Hospitalaria',
        'lima_ambulatoria' => 'Lima Ambulatoria',
        'lima_hospitalaria' => 'Lima Hospitalaria',
        'la_molina' => 'La Molina',
    ];

    protected $fillable = [
        'requester_id', 'material_category_id', 'origin_area_id',
        'material_description', 'comments', 'fields_completed',
        'pickup_address', 'pickup_contact', 'pickup_phone',
        'delivery_address', 'delivery_contact', 'delivery_phone',
        'current_status', 'evidence_image', 'rescheduled_date',
        'reschedule_comments',
        'package_image',
        'pickup_location',
        'delivery_location',
    ];

    protected static function boot()
    {
        parent::boot();

        // Cuando se intente eliminar una solicitud
        static::deleting(function($request) {
            // Verificar si la solicitud puede ser eliminada
            if ($request->current_status !== self::STATUS_PENDING) {
                throw new \Exception('No se puede eliminar una solicitud que ya fue enviada al transportista.');
            }

            $request->transporters()->delete();
            $request->statuses()->delete();
            $request->notifications()->delete();
            $request->images()->delete();
        });

        // Cuando se intente actualizar una solicitud
        static::updating(function($request) {
            // Obtener el estado original y el nuevo estado
            $originalStatus = $request->getOriginal('current_status');
            $newStatus = $request->current_status;

            // Permitir transiciones válidas de estado
            $validTransitions = [
                self::STATUS_PENDING => [self::STATUS_ACCEPTED, self::STATUS_RESCHEDULED],
                self::STATUS_ACCEPTED => [self::STATUS_COMPLETED, self::STATUS_FAILED],
                self::STATUS_RESCHEDULED => [self::STATUS_ACCEPTED],
            ];

            // Si es una transición de estado
            if ($originalStatus !== $newStatus) {
                // Verificar si la transición es válida
                if (!isset($validTransitions[$originalStatus]) ||
                    !in_array($newStatus, $validTransitions[$originalStatus])) {
                    throw new \Exception('Transición de estado no válida.');
                }
                return; // Permitir la actualización si es una transición válida
            }

            // Si no es una transición de estado, verificar si se pueden editar otros campos
            if ($originalStatus !== self::STATUS_PENDING) {
                throw new \Exception('No se puede editar una solicitud que ya fue enviada al transportista.');
            }
        });

        static::updated(function ($request) {
            // Si el estado cambió, notificar al solicitante
            if ($request->isDirty('current_status')) {
                $request->requester->notify(new MaterialRequestStatusNotification($request));
            }
        });
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function materialCategory()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }



    public function transporters()
    {
        return $this->hasMany(RequestTransporter::class, 'request_id');
    }

    public function statuses()
    {
        return $this->hasMany(RequestStatus::class, 'request_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'request_id');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'request_id');
    }

    public function latestStatus()
    {
        return $this->hasOne(RequestStatus::class)->latestOfMany();
    }

    public function currentTransporter()
    {
        return $this->hasOne(RequestTransporter::class, 'request_id')
            ->where('assignment_status', 'accepted')
            ->latestOfMany('assignment_date');
    }

    // Helper para obtener todos los estados posibles
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_ACCEPTED => 'Aceptado',
            self::STATUS_RESCHEDULED => 'Reprogramado',
            self::STATUS_COMPLETED => 'Finalizado',
            self::STATUS_FAILED => 'Fallida',
        ];
    }

    public function canEdit(): bool
    {
        return $this->current_status === self::STATUS_PENDING;
    }

    public function canDelete(): bool
    {
        return $this->current_status === self::STATUS_PENDING;
    }

    public function reschedule(): void
    {
        // Obtener el transportista actual antes de la actualización
        $currentTransporter = $this->currentTransporter;

        // Actualiza el estado a reprogramado
        $this->update([
            'current_status' => self::STATUS_RESCHEDULED,
            'current_transporter_id' => null,
            'rescheduled_date' => now(),
        ]);

        // Si existe un transportista actual, actualizamos su estado
        if ($currentTransporter) {
            $currentTransporter->update([
                'assignment_status' => 'rescheduled',
                'response_date' => now()
            ]);
        }
    }

    // Método helper para obtener las ubicaciones
    public static function getLocations(): array
    {
        return self::LOCATIONS;
    }
}
