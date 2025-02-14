<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id', 'recipient_id', 'notification_type',
        'message', 'sent', 'created_at', 'sent_at'
    ];

    const TYPE_NEW_REQUEST = 'new_request';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_ASSIGNMENT = 'assignment';
    const TYPE_COMPLETION = 'completion';

    // Relación: Una notificación pertenece a una solicitud
    public function request()
    {
        return $this->belongsTo(MaterialRequest::class, 'request_id');
    }

    // Relación: Una notificación pertenece a un destinatario (usuario)
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Scope para notificaciones no enviadas
    public function scopePending($query)
    {
        return $query->where('sent', false);
    }
}
