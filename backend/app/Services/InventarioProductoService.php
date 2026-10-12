<?php

namespace App\Services;

use App\DTOs\InventarioProductoDTO;
use App\Models\InventarioProducto;

class InventarioProductoService
{
    // Solo activos
    public function listar(int $perPage = 100)
    {
        return InventarioProducto::with(['producto', 'lote'])
            ->where('activo', true)
            ->orderBy('id_inventario', 'desc')
            ->paginate($perPage);
    }

    // Solo inactivos (Archivados)
    public function listarInactivos(int $perPage = 100)
    {
        return InventarioProducto::with(['producto', 'lote'])
            ->where('activo', false)
            ->orderBy('id_inventario', 'desc')
            ->paginate($perPage);
    }

    public function crear(InventarioProductoDTO $dto): InventarioProducto
    {
        return InventarioProducto::create([
            'producto_id'        => $dto->producto_id,
            'cantidad'           => $dto->cantidad,
            'stock'              => $dto->stock,
            'lote_id'            => $dto->lote_id,
            'deteccion_id'       => $dto->deteccion_id,
            'ingreso_detalle_id' => $dto->ingreso_detalle_id,
            'clasificacion'      => $dto->clasificacion,
            'ruta_evidencia'     => $dto->ruta_evidencia,
            'activo'             => true, // siempre activo al crear
        ]);
    }

    public function actualizar(InventarioProducto $inventarioProducto, InventarioProductoDTO $dto): InventarioProducto
    {
        $inventarioProducto->update([
            'producto_id'        => $dto->producto_id,
            'cantidad'           => $dto->cantidad,
            'stock'              => $dto->stock,
            'lote_id'            => $dto->lote_id,
            'deteccion_id'       => $dto->deteccion_id,
            'ingreso_detalle_id' => $dto->ingreso_detalle_id,
            'clasificacion'      => $dto->clasificacion,
            'ruta_evidencia'     => $dto->ruta_evidencia,
        ]);

        return $inventarioProducto->fresh();
    }

    // Soft-delete: pone activo = false
    public function eliminar(InventarioProducto $inventarioProducto): void
    {
        $inventarioProducto->update(['activo' => false]);
    }

    // Reactiva: pone activo = true
    public function activar(InventarioProducto $inventarioProducto): InventarioProducto
    {
        $inventarioProducto->update(['activo' => true]);
        return $inventarioProducto->fresh();
    }

    // Borra físicamente de la BD
    public function eliminarDefinitivo(InventarioProducto $inventarioProducto): void
    {
        $inventarioProducto->delete();
    }
}
