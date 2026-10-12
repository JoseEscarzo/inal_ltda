<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IngresoRevision extends Model
{
    protected $table = 'ingresos_revision';
    protected $primaryKey = 'id_ingreso';
    public $timestamps = false;

    protected $fillable = ['entregado_por_id', 'proveedor_id', 'recibido_por_id', 'fecha_hora', 'turno', 'observaciones'];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

      public function detalles()
    {
        return $this->hasMany(IngresoDetalle::class, 'ingreso_id', 'id_ingreso');
    }

    public function entregadoPor()
    {
        return $this->belongsTo(User::class, 'entregado_por_id', 'id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id_proveedor');
    }

    public function recibidoPor()
    {
        return $this->belongsTo(User::class, 'recibido_por_id', 'id');
    }
}
