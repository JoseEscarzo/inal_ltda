<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoReceta extends Model
{
    protected $table = 'producto_recetas';
    protected $primaryKey = 'id_receta';

    protected $fillable = [
        'producto_id',
        'materia_prima_id',
        'cantidad_por_unidad',
        'cantidad_total',
        'unidades_producidas',
        'unidad',
    ];

    protected $casts = [
        'cantidad_por_unidad' => 'decimal:3',
        'cantidad_total' => 'decimal:3',
        'unidades_producidas' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'id_producto');
    }

    public function materiaPrima()
    {
        return $this->belongsTo(Producto::class, 'materia_prima_id', 'id_producto');
    }
}
