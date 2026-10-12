<?php

namespace App\Services;

use App\DTOs\SalidaRevisionDTO;
use App\Models\Alerta;
use App\Models\Distribucion;
use App\Models\InventarioProducto;
use App\Models\Lote;
use App\Models\SalidaRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalidaRevisionService
{
    public function listar(int $perPage = 15)
    {
        return SalidaRevision::with([
            'detalles.producto',
            'detalles.lote',
            'despachadoPor',
            'autorizadoPor',
            'distribuidor',
            'destino',
            'factura.cliente',
        ])
        ->orderByDesc('fecha_hora')
        ->paginate($perPage);
    }

    /**
     * Genera un nro_informe único automáticamente.
     * Formato: SAL-2026-0001
     */
    private function generarNroInforme(): string
    {
        $year = now()->year;
        $ultimo = SalidaRevision::whereYear('fecha_hora', $year)
            ->orWhereNull('fecha_hora')
            ->max('id_salida') ?? 0;

        // Usamos el próximo ID estimado como correlativo
        $siguiente = $ultimo + 1;
        return 'SAL-' . $year . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    public function crear(SalidaRevisionDTO $dto, User $creadoPor): SalidaRevision
    {
        return DB::transaction(function () use ($dto, $creadoPor) {
            $esSupervisor = (int) $creadoPor->id_rol === 2;
            $autorizadoPorId = $esSupervisor ? $dto->autorizado_por_id : $creadoPor->id;

            if ($esSupervisor) {
                $administrador = User::query()
                    ->where('id', $autorizadoPorId)
                    ->where('id_rol', 1)
                    ->first();

                if (!$administrador) {
                    throw ValidationException::withMessages([
                        'autorizado_por_id' => ['Seleccione un administrador para autorizar la salida.'],
                    ]);
                }
            }

            $salida = SalidaRevision::create([
                'nro_informe'       => $this->generarNroInforme(),
                'despachado_por_id' => $esSupervisor ? $creadoPor->id : $dto->despachado_por_id,
                'autorizado_por_id' => $autorizadoPorId,
                'distribuidor_id'   => $dto->distribuidor_id,
                'destino_id'        => $dto->destino_id,
                'turno'             => $dto->turno,
                'observaciones'     => $dto->observaciones,
                'estado_aprobacion' => $esSupervisor ? 'pendiente' : 'aprobada',
                'aprobado_en'       => $esSupervisor ? null : now(),
                // fecha_hora tiene DEFAULT CURRENT_TIMESTAMP
            ]);

            foreach ($dto->detalles as $detalle) {
                $loteId = $detalle->lote_id ?: $this->resolverLoteProducto($detalle->producto_id);

                $salidaDetalle = $salida->detalles()->create([
                    'producto_id' => $detalle->producto_id,
                    'cantidad'    => $detalle->cantidad,
                    'lote_id'     => $loteId,
                ]);

                if (!$esSupervisor) {
                    $this->crearDistribucionEInventario($salida, $salidaDetalle);
                }
            }

            if ($esSupervisor) {
                Alerta::create([
                    'salida_id' => $salida->id_salida,
                    'usuario_destino_id' => $autorizadoPorId,
                    'nivel' => 'pendiente',
                    'fecha_hora' => now(),
                    'estado' => 'pendiente',
                    'mensaje' => 'Salida pendiente de aprobacion: '.$salida->nro_informe,
                    'ruta_evidencia' => '/distribuciones',
                ]);
            }

            return $salida->load([
                'detalles.producto',
                'detalles.lote',
                'despachadoPor',
                'autorizadoPor',
                'distribuidor',
                'destino',
                'factura.cliente',
            ]);
        });
    }

    public function aprobar(SalidaRevision $salida, User $user): SalidaRevision
    {
        return DB::transaction(function () use ($salida, $user) {
            if ((int) $salida->autorizado_por_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'autorizado_por_id' => ['Solo el administrador autorizado puede aprobar esta salida.'],
                ]);
            }

            if ($salida->estado_aprobacion !== 'pendiente') {
                return $salida->load(['detalles.producto', 'detalles.lote', 'despachadoPor', 'autorizadoPor', 'distribuidor', 'destino', 'factura.cliente']);
            }

            $salida->load('detalles');

            foreach ($salida->detalles as $detalle) {
                $this->crearDistribucionEInventario($salida, $detalle);
            }

            $salida->update([
                'estado_aprobacion' => 'aprobada',
                'aprobado_en' => now(),
            ]);

            Alerta::where('salida_id', $salida->id_salida)->update(['estado' => 'aprobada']);

            Alerta::firstOrCreate(
                [
                    'salida_id' => $salida->id_salida,
                    'usuario_destino_id' => $salida->distribuidor_id,
                ],
                [
                    'nivel' => 'recibo',
                    'fecha_hora' => now(),
                    'estado' => 'pendiente',
                    'mensaje' => 'Recibo de productos designados: '.$salida->nro_informe,
                    'ruta_evidencia' => '/trazabilidad',
                ]
            );

            return $salida->fresh()->load(['detalles.producto', 'detalles.lote', 'despachadoPor', 'autorizadoPor', 'distribuidor', 'destino', 'factura.cliente']);
        });
    }

    public function rechazar(SalidaRevision $salida, User $user): void
    {
        DB::transaction(function () use ($salida, $user) {
            if ((int) $salida->autorizado_por_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'autorizado_por_id' => ['Solo el administrador autorizado puede rechazar esta salida.'],
                ]);
            }

            Alerta::where('salida_id', $salida->id_salida)->update(['estado' => 'rechazada']);
            $salida->delete();
        });
    }

    public function eliminar(SalidaRevision $salida): void
    {
        $salida->delete();
    }

    private function resolverLoteProducto(int $productoId): ?int
    {
        return Lote::query()
            ->where('producto_id', $productoId)
            ->where('cantidad', '>', 0)
            ->whereIn('estado', ['activo', 'por vencer'])
            ->orderByRaw("CASE WHEN estado = 'por vencer' THEN 0 ELSE 1 END")
            ->orderBy('fecha_fin')
            ->orderBy('id_lote')
            ->value('id_lote');
    }

    private function crearDistribucionEInventario(SalidaRevision $salida, $salidaDetalle): void
    {
        $loteId = $salidaDetalle->lote_id ?: $this->resolverLoteProducto($salidaDetalle->producto_id);

        if ($loteId && !$salidaDetalle->lote_id) {
            $salidaDetalle->lote_id = $loteId;
            $salidaDetalle->save();
        }

        if ($loteId && $salida->destino_id && $salida->distribuidor_id) {
            Distribucion::firstOrCreate(
                ['salida_detalle_id' => $salidaDetalle->id_detalle_salida],
                [
                    'salida_id' => $salida->id_salida,
                    'lote_id' => $loteId,
                    'destino_id' => $salida->destino_id,
                    'responsable_id' => $salida->distribuidor_id,
                    'fecha_envio' => now(),
                    'cantidad_productos' => $salidaDetalle->cantidad,
                    'estado' => 'designado',
                    'observaciones' => $salida->observaciones,
                ]
            );
        }

        $stockAnterior = InventarioProducto::where('producto_id', $salidaDetalle->producto_id)
            ->orderByDesc('id_inventario')
            ->value('stock') ?? 0;

        if ($stockAnterior < $salidaDetalle->cantidad) {
            throw ValidationException::withMessages([
                'detalles' => ['Stock insuficiente para el producto '.$salidaDetalle->producto_id.'.'],
            ]);
        }

        if ($loteId) {
            $lote = Lote::lockForUpdate()->find($loteId);
            if (!$lote || (int) $lote->producto_id !== (int) $salidaDetalle->producto_id) {
                throw ValidationException::withMessages([
                    'detalles' => ['El lote seleccionado no corresponde al producto indicado.'],
                ]);
            }

            if (!in_array($lote->estado, ['activo', 'por vencer'], true)) {
                throw ValidationException::withMessages([
                    'detalles' => ['El lote '.$lote->codigo_lote.' no esta disponible para salida.'],
                ]);
            }

            if ($lote && (float) $lote->cantidad < (float) $salidaDetalle->cantidad) {
                throw ValidationException::withMessages([
                    'detalles' => ['Stock insuficiente en el lote '.$lote->codigo_lote.'.'],
                ]);
            }

            if ($lote) {
                $lote->cantidad = max(0, (float) $lote->cantidad - (float) $salidaDetalle->cantidad);
                $lote->save();
            }
        }

        InventarioProducto::create([
            'producto_id' => $salidaDetalle->producto_id,
            'lote_id' => $loteId,
            'clasificacion' => 'salida',
            'cantidad' => $salidaDetalle->cantidad,
            'stock' => $stockAnterior - $salidaDetalle->cantidad,
            'fecha_registro' => now(),
            'activo' => true,
        ]);
    }
}
