<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConfirmacionAlerta extends Model
{
    protected $table = 'confirmaciones_alerta';
    protected $primaryKey = 'id_confirmacion';
    public $timestamps = false;

    protected $fillable = ['alerta_id', 'usuario_id', 'fecha_confirmacion', 'observacion'];
}
