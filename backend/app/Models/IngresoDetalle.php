<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IngresoDetalle extends Model
{
    protected $table = 'ingreso_detalles';
    protected $primaryKey = 'id_detalle_ingreso';
    public $timestamps = false;

    protected $fillable = ['ingreso_id', 'producto_id', 'lote_id', 'cantidad', 'cantidad_aprobada', 'cantidad_rechazada'];

    public function ingreso() {
        return $this->belongsTo(IngresoRevision::class, 'ingreso_id', 'id_ingreso');
    }

    public function producto() {
        return $this->belongsTo(Producto::class, 'producto_id', 'id_producto');
    }

    public function lote() {
        return $this->belongsTo(Lote::class, 'lote_id', 'id_lote');
    }
}
