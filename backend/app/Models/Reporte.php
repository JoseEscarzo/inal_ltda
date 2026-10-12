<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $table = 'reportes';
    protected $primaryKey = 'id_reporte';
    public $timestamps = false;

    protected $fillable = ['generado_por', 'tipo', 'parametros', 'fecha_generacion', 'formato', 'ruta_archivo'];

    public function usuario() {
        return $this->belongsTo(User::class, 'generado_por', 'id');
    }
}

