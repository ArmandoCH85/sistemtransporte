<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id', 'type', 'image_url'
    ];

    const TYPE_PICKUP = 'pickup';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_DAMAGE = 'damage';
    const TYPE_OTHER = 'other';

    // Relación: Una imagen pertenece a una solicitud
    public function request()
    {
        return $this->belongsTo(MaterialRequest::class, 'request_id');
    }
}
