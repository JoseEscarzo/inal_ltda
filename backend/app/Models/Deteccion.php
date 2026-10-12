<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Deteccion extends Model
{
    protected $table = 'detecciones';
    protected $primaryKey = 'id_deteccion';
    public $timestamps = false;

    protected $fillable = ['camara_id', 'modelo_id', 'tipo_defecto_id', 'lote_id', 'fecha_hora', 'nivel_confianza', 'resultado', 'ruta_imagen'];

    public function camara() { return $this->belongsTo(Camara::class, 'camara_id', 'id_camara'); }
    public function lote() { return $this->belongsTo(Lote::class, 'lote_id', 'id_lote'); }
}
