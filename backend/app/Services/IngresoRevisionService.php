<?php

namespace App\Services;

use App\DTOs\IngresoRevisionDTO;
use App\Models\IngresoRevision;
use App\Models\InventarioProducto;
use Illuminate\Support\Facades\DB;

class IngresoRevisionService
{
    public function listar(int $perPage = 15)
    {
        return IngresoRevision::with([
            'detalles.producto',
            'entregadoPor',
            'proveedor',
            'recibidoPor',
        ])
        ->orderByDesc('fecha_hora')
        ->paginate($perPage);
    }

    public function crear(IngresoRevisionDTO $dto): IngresoRevision
    {
        return DB::transaction(function () use ($dto) {
            $ingreso = IngresoRevision::create([
                'entregado_por_id' => $dto->entregado_por_id,
                'proveedor_id'     => $dto->proveedor_id,
                'recibido_por_id'  => $dto->recibido_por_id,
                'turno'            => $dto->turno,
                'observaciones'    => $dto->observaciones,
                // fecha_hora tiene DEFAULT CURRENT_TIMESTAMP en la BD, no hace falta enviarlo
            ]);

            foreach ($dto->detalles as $detalle) {
                // BUG FIX: la PK es id_detalle_ingreso, no "id"
                $nuevoDetalle = $ingreso->detalles()->create([
                    'producto_id'        => $detalle->producto_id,
                    'cantidad'           => $detalle->cantidad,
                    'lote_id'            => $detalle->lote_id,
                    'cantidad_aprobada'  => $detalle->cantidad_aprobada,
                    'cantidad_rechazada' => $detalle->cantidad_rechazada,
                ]);

                if ($detalle->cantidad_aprobada > 0) {
                    $stockAnterior = InventarioProducto::where('producto_id', $detalle->producto_id)
                        ->orderByDesc('id_inventario')
                        ->value('stock') ?? 0;

                    InventarioProducto::create([
                        'producto_id'        => $detalle->producto_id,
                        'lote_id'            => $detalle->lote_id,
                        'clasificacion'      => 'ingreso',
                        'cantidad'           => $detalle->cantidad_aprobada,
                        'stock'              => $stockAnterior + $detalle->cantidad_aprobada,
                        'ingreso_detalle_id' => $nuevoDetalle->id_detalle_ingreso, // ← BUG FIX
                        'fecha_registro'     => now(),
                        'deteccion_id'       => null,
                        'ruta_evidencia'     => null,
                    ]);
                }

                if ($detalle->cantidad_rechazada > 0) {
                    $stockActual = InventarioProducto::where('producto_id', $detalle->producto_id)
                        ->orderByDesc('id_inventario')
                        ->value('stock') ?? 0;

                    InventarioProducto::create([
                        'producto_id'        => $detalle->producto_id,
                        'lote_id'            => $detalle->lote_id,
                        'clasificacion'      => 'rechazado',
                        'cantidad'           => $detalle->cantidad_rechazada,
                        'stock'              => $stockActual,
                        'ingreso_detalle_id' => $nuevoDetalle->id_detalle_ingreso, // ← BUG FIX
                        'fecha_registro'     => now(),
                        'deteccion_id'       => null,
                        'ruta_evidencia'     => null,
                    ]);
                }
            }

            return $ingreso->load([
                'detalles.producto',
                'entregadoPor',
                'proveedor',
                'recibidoPor',
            ]);
        });
    }

    public function eliminar(IngresoRevision $ingreso): void
    {
        $ingreso->delete();
    }
}
