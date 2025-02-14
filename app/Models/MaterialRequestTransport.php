<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequestTransport extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'requests';

    // Definimos las constantes de estado
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'requester_id', 'material_category_id', 'origin_area_id',
        'material_description', 'comments', 'fields_completed',
        'pickup_address', 'pickup_contact', 'pickup_phone',
        'delivery_address', 'delivery_contact', 'delivery_phone',
        'current_status', 'evidence_image', 'rescheduled_date',
        'reschedule_comments'
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
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function materialCategory()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function originArea()
    {
        return $this->belongsTo(Area::class, 'origin_area_id');
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


}
