<?php

namespace App\Services;

use App\Models\Camara;

class CamaraService
{
    public function listar()
    {
        return Camara::with('linea')->orderBy('nombre')->get();
    }

    public function crear(array $data): Camara
    {
        return Camara::create([
            ...$data,
            'estado' => $data['estado'] ?? 'Activa',
            'created_at' => now(),
        ])->load('linea');
    }

    public function actualizar(Camara $camara, array $data): Camara
    {
        $camara->update($data);
        return $camara->fresh('linea');
    }

    public function eliminar(Camara $camara): void
    {
        $camara->delete();
    }
}
