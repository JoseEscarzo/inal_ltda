<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SalidaRevision extends Model
{
    protected $table = 'salidas_revision';
    protected $primaryKey = 'id_salida';
    public $timestamps = false;

    protected $fillable = ['nro_informe', 'despachado_por_id', 'autorizado_por_id', 'distribuidor_id', 'destino_id', 'fecha_hora', 'turno', 'observaciones', 'estado_aprobacion', 'aprobado_en', 'rechazado_en'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'aprobado_en' => 'datetime',
        'rechazado_en' => 'datetime',
    ];

     public function detalles()
    {
        return $this->hasMany(SalidaDetalle::class, 'salida_id', 'id_salida');
    }

    public function despachadoPor()
    {
        return $this->belongsTo(User::class, 'despachado_por_id', 'id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por_id', 'id');
    }

    public function distribuidor()
    {
        return $this->belongsTo(User::class, 'distribuidor_id', 'id');
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class, 'destino_id', 'id_destino');
    }

    public function factura()
    {
        return $this->hasOne(Factura::class, 'salida_id', 'id_salida');
    }
}
