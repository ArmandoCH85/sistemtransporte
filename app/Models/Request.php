<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id', 'material_category_id', 'origin_area_id',
        'material_description', 'comments', 'fields_completed',
        'pickup_address', 'pickup_contact', 'pickup_phone',
        'delivery_address', 'delivery_contact', 'delivery_phone',
        'current_status'
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Relación: Una solicitud pertenece a una categoría de material
    public function materialCategory()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    // Relación: Una solicitud pertenece a un área de origen
    public function originArea()
    {
        return $this->belongsTo(Area::class, 'origin_area_id');
    }

    // Relación: Una solicitud tiene muchas asignaciones a transportistas
    public function transporters()
    {
        return $this->hasMany(RequestTransporter::class, 'request_id');
    }

    // Relación: Una solicitud tiene muchos estados
    public function statuses()
    {
        return $this->hasMany(RequestStatus::class, 'request_id');
    }

    // Relación: Una solicitud tiene muchas notificaciones
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'request_id');
    }

    // Relación: Una solicitud tiene muchas imágenes
    public function images()
    {
        return $this->hasMany(Image::class, 'request_id');
    }

    // Obtener el último estado de la solicitud
    public function latestStatus()
    {
        return $this->hasOne(RequestStatus::class)->latestOfMany();
    }

    // Obtener el transportista actualmente asignado
    public function currentTransporter()
    {
        return $this->hasOne(RequestTransporter::class)
            ->where('assignment_status', RequestTransporter::STATUS_ACCEPTED)
            ->latestOfMany('assignment_date');
    }
}
