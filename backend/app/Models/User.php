<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'phone',
    'password',
    'otp_code',
    'otp_expires_at',
    'login_otp_code',
    'login_otp_expires_at',
    'email_verified_at',
    'face_photo_base64',
    'face_descriptor',
    'id_rol',
    'fecha_nacimiento',  // NUEVO
    'nro_carnet',        // NUEVO
    'activo',            // NUEVO
])]
#[Hidden(['password', 'remember_token', 'face_photo_base64'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'otp_expires_at'         => 'datetime',
            'login_otp_expires_at'   => 'datetime',
            'password'               => 'hashed',
            'face_descriptor'        => 'array',
            'fecha_nacimiento'       => 'date',   // NUEVO
            'activo'                 => 'boolean', // NUEVO
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}
