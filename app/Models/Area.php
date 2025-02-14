<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'is_active', 'code'
    ];

    // Relación: Un área pertenece a muchos usuarios
    public function users()
    {
        return $this->hasMany(User::class, 'area_id');
    }

    // Relación: Un área origina muchas solicitudes
    public function requests()
    {
        return $this->hasMany(MaterialRequest::class, 'origin_area_id');
    }

    // Scope para áreas activas
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}
