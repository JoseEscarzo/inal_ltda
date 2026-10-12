<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = ['nombre', 'codigo', 'descripcion', 'categoria', 'tipo_producto', 'presentacion', 'precio_costo', 'precio_distribuidor', 'precio_venta_publico', 'activo', 'created_at'];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'precio_costo' => 'decimal:2',
        'precio_distribuidor' => 'decimal:2',
        'precio_venta_publico' => 'decimal:2',
    ];

    public function inventarios() {
        return $this->hasMany(InventarioProducto::class, 'producto_id', 'id_producto');
    }

    public function receta() {
        return $this->hasMany(ProductoReceta::class, 'producto_id', 'id_producto');
    }

}
