<?php

namespace App\Services;

use App\DTOs\LineaProduccionDTO;
use App\Models\LineaProduccion;

class LineaProduccionService
{
    public function listar(int $perPage = 15)
    {
        return LineaProduccion::with('lotes.producto')->orderBy('nombre')->paginate($perPage);
    }

    public function crear(LineaProduccionDTO $dto): LineaProduccion
    {
        return LineaProduccion::create([
            'nombre'      => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'estado'      => $dto->estado,
            'turno'       => $dto->turno,
        ]);
    }

    public function actualizar(LineaProduccion $linea, LineaProduccionDTO $dto): LineaProduccion
    {
        $linea->update([
            'nombre'      => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'estado'      => $dto->estado,
            'turno'       => $dto->turno,
        ]);

        return $linea->fresh()->load('lotes.producto');
    }

    public function eliminar(LineaProduccion $linea): void
    {
        $linea->delete();
    }
}
