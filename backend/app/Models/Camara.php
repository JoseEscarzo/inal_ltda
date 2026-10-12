<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Camara extends Model
{
    protected $table = 'camaras';
    protected $primaryKey = 'id_camara';
    public $timestamps = false;

    protected $fillable = ['nombre', 'linea_id', 'url_stream', 'estado', 'ubicacion', 'created_at'];

    public function linea() {
        return $this->belongsTo(LineaProduccion::class, 'linea_id', 'id_linea');
    }
}
