<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'destino_id',
        'razon_social',
        'nit_ci',
        'nombre_encargado',
        'telefono',
        'correo',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function destino()
    {
        return $this->belongsTo(Destino::class, 'destino_id', 'id_destino');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'cliente_id', 'id_cliente');
    }
}
