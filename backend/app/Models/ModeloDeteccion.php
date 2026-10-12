<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ModeloDeteccion extends Model
{
    protected $table = 'modelos_deteccion';
    protected $primaryKey = 'id_modelo';
    public $timestamps = false;

    protected $fillable = ['nombre', 'version', 'ruta_archivo', 'umbral_confianza', 'activo', 'descripcion'];
}
