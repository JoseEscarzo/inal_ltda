<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';
    protected $primaryKey = 'id_factura';

    protected $fillable = [
        'salida_id',
        'cliente_id',
        'emitido_por_id',
        'distribuidor_id',
        'numero_factura',
        'codigo_autorizacion',
        'fecha_emision',
        'subtotal',
        'descuento',
        'total_pagar',
        'estado',
        'codigo_qr',
        'url_qr',
        'metodo_pago',
        'estado_pago',
        'correo_enviado_at',
        'correo_destino',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'correo_enviado_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total_pagar' => 'decimal:2',
    ];

    public function salida()
    {
        return $this->belongsTo(SalidaRevision::class, 'salida_id', 'id_salida');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id_cliente');
    }

    public function emitidoPor()
    {
        return $this->belongsTo(User::class, 'emitido_por_id', 'id');
    }

    public function distribuidor()
    {
        return $this->belongsTo(User::class, 'distribuidor_id', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'factura_id', 'id_factura');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'factura_id', 'id_factura');
    }
}
