<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $table = 'alertas';
    protected $primaryKey = 'id_alerta';
    public $timestamps = false;

    protected $fillable = ['deteccion_id', 'salida_id', 'usuario_destino_id', 'nivel', 'fecha_hora', 'estado', 'mensaje', 'ruta_evidencia'];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function salida()
    {
        return $this->belongsTo(SalidaRevision::class, 'salida_id', 'id_salida');
    }
}
