<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialCategory extends Model
{
    use HasFactory;


    protected $fillable = [
        'name', 'description'
    ];

    // Relación: Una categoría tiene muchas solicitudes
    public function requests()
    {
        return $this->hasMany(MaterialRequest::class, 'material_category_id');
    }
}
