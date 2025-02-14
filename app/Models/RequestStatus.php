<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id', 'user_id', 'status', 'comment', 'notified'
    ];

    // Relación: Un estado pertenece a una solicitud
    public function request()
    {
        return $this->belongsTo(MaterialRequest::class, 'request_id');
    }

    // Relación: Un estado fue registrado por un usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scope para estados activos
    public function scopeActive($query)
    {
        return $query->whereNull('end_date');
    }

    // Relación con el siguiente estado
    public function nextStatus()
    {
        return $this->hasOne(RequestStatus::class, 'previous_status_id');
    }
}
