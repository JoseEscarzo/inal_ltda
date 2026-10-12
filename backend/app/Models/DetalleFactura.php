<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    protected $table = 'detalle_factura';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'factura_id',
        'producto_id',
        'lote_id',
        'cantidad',
        'precio_venta',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_venta' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id', 'id_factura');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'id_producto');
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id', 'id_lote');
    }
}
