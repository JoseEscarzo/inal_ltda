<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InventarioProducto extends Model
{
    protected $table = 'inventario_productos';
    protected $primaryKey = 'id_inventario';
    public $timestamps = false;

    protected $fillable = ['deteccion_id', 'producto_id', 'lote_id', 'clasificacion', 'ruta_evidencia', 'fecha_registro', 'cantidad', 'ingreso_detalle_id', 'stock', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_registro' => 'datetime'
    ];

    public function producto() {
        return $this->belongsTo(Producto::class, 'producto_id', 'id_producto');
    }

    public function lote() {
        return $this->belongsTo(Lote::class, 'lote_id', 'id_lote');
    }

    public function ingresoDetalle() {
        return $this->belongsTo(IngresoDetalle::class, 'ingreso_detalle_id', 'id_detalle_ingreso');
    }
}
