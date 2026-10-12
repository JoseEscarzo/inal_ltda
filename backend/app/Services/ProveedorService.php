<?php

namespace App\Services;

use App\DTOs\ProveedorDTO;
use App\Models\Proveedor;

class ProveedorService
{
    public function listar()
    {
        return Proveedor::where('activo', true)->orderBy('nombre')->get();
    }

    public function inactivos()
    {
        return Proveedor::where('activo', false)->orderBy('nombre')->get();
    }

    public function crear(ProveedorDTO $dto): Proveedor
    {
        return Proveedor::create($this->datos($dto));
    }

    public function actualizar(Proveedor $proveedor, ProveedorDTO $dto): Proveedor
    {
        $proveedor->update($this->datos($dto));
        return $proveedor->fresh();
    }

    public function archivar(Proveedor $proveedor): Proveedor
    {
        $proveedor->update(['activo' => false]);
        return $proveedor->fresh();
    }

    public function activar(Proveedor $proveedor): Proveedor
    {
        $proveedor->update(['activo' => true]);
        return $proveedor->fresh();
    }

    public function eliminar(Proveedor $proveedor): void
    {
        $proveedor->delete();
    }

    private function datos(ProveedorDTO $dto): array
    {
        return [
            'nombre' => $dto->nombre,
            'nit' => $dto->nit,
            'contacto' => $dto->contacto,
            'telefono' => $dto->telefono,
            'correo' => $dto->correo,
            'direccion' => $dto->direccion,
            'activo' => $dto->activo,
        ];
    }
}
