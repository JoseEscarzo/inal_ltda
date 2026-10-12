<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destinos';
    protected $primaryKey = 'id_destino';
    public $timestamps = false;

    protected $fillable = ['nombre', 'ciudad', 'tipo', 'direccion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function salidas() {
        return $this->hasMany(SalidaRevision::class, 'destino_id', 'id_destino');
    }

    public function cliente() {
        return $this->hasOne(Cliente::class, 'destino_id', 'id_destino');
    }
}
