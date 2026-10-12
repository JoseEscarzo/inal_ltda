<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LineaProduccion extends Model
{
    protected $table = 'lineas_produccion';
    protected $primaryKey = 'id_linea';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'estado', 'turno'];

    public function lotes() {
        return $this->hasMany(Lote::class, 'linea_id', 'id_linea');
    }
}
