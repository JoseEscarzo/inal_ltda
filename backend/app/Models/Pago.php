<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'factura_id',
        'monto',
        'metodo',
        'estado',
        'fecha_pago',
        'referencia',
        'registrado_por_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id', 'id_factura');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_id', 'id');
    }
}
