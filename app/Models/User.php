<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


// Relación: Un usuario pertenece a un área
public function area()
{
    return $this->belongsTo(Area::class, 'area_id');
}

// Relación: Un usuario crea muchas solicitudes
public function requests()
{
    return $this->hasMany(Request::class, 'requester_id');
}

// Relación: Un usuario puede ser asignado como transportista en muchas solicitudes
public function transporterAssignments()
{
    return $this->hasMany(RequestTransporter::class, 'transporter_id');
}

// Relación: Un usuario registra muchos estados de solicitud
public function requestStatuses()
{
    return $this->hasMany(RequestStatus::class, 'user_id');
}

// Relación: Un usuario recibe muchas notificaciones
public function notifications()
{
    return $this->hasMany(Notification::class, 'recipient_id');
}




}
