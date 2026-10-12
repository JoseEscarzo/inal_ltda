<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $primaryKey = 'id_proveedor';

    protected $fillable = [
        'nombre', 'nit', 'contacto', 'telefono', 'correo', 'direccion', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function ingresos()
    {
        return $this->hasMany(IngresoRevision::class, 'proveedor_id', 'id_proveedor');
    }
}
