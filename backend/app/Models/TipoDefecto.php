<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipoDefecto extends Model
{
    protected $table = 'tipos_defecto';
    protected $primaryKey = 'id_tipo_defecto';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];
}
