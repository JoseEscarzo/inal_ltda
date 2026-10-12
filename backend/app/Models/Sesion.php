<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sesion extends Model
{
    protected $table = 'sessions';

    // Configuraciones clave para una Primary Key que es un String
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'
    ];

    /**
     * Relación con el usuario dueño de la sesión
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
