<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Distribucion extends Model
{
    protected $table = 'distribuciones';
    protected $primaryKey = 'id_distribucion';
    public $timestamps = false;

    protected $fillable = ['lote_id', 'destino_id', 'responsable_id', 'salida_id', 'salida_detalle_id', 'fecha_envio', 'cantidad_productos', 'estado', 'observaciones'];

    protected $casts = [
        'fecha_envio' => 'datetime',
    ];

    public function lote() { return $this->belongsTo(Lote::class, 'lote_id', 'id_lote'); }
    public function destino() { return $this->belongsTo(Destino::class, 'destino_id', 'id_destino'); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id', 'id'); }
    public function salida() { return $this->belongsTo(SalidaRevision::class, 'salida_id', 'id_salida'); }
    public function salidaDetalle() { return $this->belongsTo(SalidaDetalle::class, 'salida_detalle_id', 'id_detalle_salida'); }
}
