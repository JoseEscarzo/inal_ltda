<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SalidaDetalle extends Model
{
    protected $table = 'salida_detalles';
    protected $primaryKey = 'id_detalle_salida';
    public $timestamps = false;

    protected $fillable = ['salida_id', 'producto_id', 'lote_id', 'cantidad'];

    public function salida() { return $this->belongsTo(SalidaRevision::class, 'salida_id', 'id_salida'); }
    public function producto() { return $this->belongsTo(Producto::class, 'producto_id', 'id_producto'); }
    public function lote() { return $this->belongsTo(Lote::class, 'lote_id', 'id_lote'); }
}
