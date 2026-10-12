<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'lotes';
    protected $primaryKey = 'id_lote';
    public $timestamps = false;

    protected $fillable = ['codigo_lote', 'producto_id', 'linea_id', 'usuario_id', 'cantidad', 'fecha_inicio', 'fecha_fin', 'turno', 'estado', 'observaciones'];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'cantidad' => 'decimal:3',
    ];

    public function producto() {
        return $this->belongsTo(Producto::class, 'producto_id', 'id_producto');
    }

    public function linea() {
        return $this->belongsTo(LineaProduccion::class, 'linea_id', 'id_linea');
    }

    public function usuario() {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    public function distribuciones() {
        return $this->hasMany(Distribucion::class, 'lote_id', 'id_lote');
    }
}
