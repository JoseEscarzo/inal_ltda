<?php

namespace App\Services;

use App\DTOs\DistribucionDTO;
use App\Models\Distribucion;
use App\Models\Lote;
use App\Models\SalidaRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DistribucionService
{
    public function listar(User $user, array $filters = [], int $perPage = 100)
    {
        $this->sincronizarSalidas();

        return $this->query($user, $filters)
            ->orderByDesc('fecha_envio')
            ->paginate($perPage);
    }

    public function crear(DistribucionDTO $dto): Distribucion
    {
        return Distribucion::create([
            'lote_id' => $dto->lote_id,
            'destino_id' => $dto->destino_id,
            'responsable_id' => $dto->responsable_id,
            'fecha_envio' => $dto->fecha_envio ?: now(),
            'cantidad_productos' => $dto->cantidad_productos,
            'estado' => 'designado',
            'observaciones' => $dto->observaciones,
        ])->load(['lote.producto', 'destino', 'responsable']);
    }

    public function actualizar(Distribucion $distribucion, DistribucionDTO $dto, User $user): Distribucion
    {
        if ((int) $user->id_rol === 5) {
            if ((int) $distribucion->responsable_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'responsable_id' => ['Solo puedes actualizar rutas asignadas a tu usuario.'],
                ]);
            }

            $distribucion->update([
                'estado' => 'entregado',
                'observaciones' => $dto->observaciones,
            ]);

            return $distribucion->fresh()->load(['lote.producto', 'destino', 'responsable']);
        }

        $distribucion->update([
            'lote_id' => $dto->lote_id,
            'destino_id' => $dto->destino_id,
            'responsable_id' => $dto->responsable_id,
            'fecha_envio' => $dto->fecha_envio,
            'cantidad_productos' => $dto->cantidad_productos,
            'estado' => $distribucion->estado ?: 'designado',
            'observaciones' => $dto->observaciones,
        ]);

        return $distribucion->fresh()->load(['lote.producto', 'destino', 'responsable']);
    }

    public function eliminar(Distribucion $distribucion): void
    {
        $distribucion->delete();
    }

    public function query(User $user, array $filters = []): Builder
    {
        $this->sincronizarSalidas();

        $buscar = $filters['buscar'] ?? null;
        $estado = $filters['estado'] ?? null;
        $destinoId = $filters['destino_id'] ?? null;
        $loteId = $filters['lote_id'] ?? null;

        return Distribucion::query()
            ->with(['lote.producto', 'lote.linea', 'destino', 'responsable', 'salida'])
            ->when((int) $user->id_rol === 5, function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($destinoId, fn ($q) => $q->where('destino_id', $destinoId))
            ->when($loteId, fn ($q) => $q->where('lote_id', $loteId))
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('estado', 'ILIKE', "%{$buscar}%")
                        ->orWhere('observaciones', 'ILIKE', "%{$buscar}%")
                        ->orWhereHas('lote', fn ($l) => $l->where('codigo_lote', 'ILIKE', "%{$buscar}%"))
                        ->orWhereHas('lote.producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"))
                        ->orWhereHas('destino', fn ($d) => $d->where('nombre', 'ILIKE', "%{$buscar}%"))
                        ->orWhereHas('responsable', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"));
                });
            });
    }

    private function sincronizarSalidas(): void
    {
        SalidaRevision::query()
            ->with('detalles')
            ->whereNotNull('destino_id')
            ->whereNotNull('distribuidor_id')
            ->where(function ($q) {
                $q->whereNull('estado_aprobacion')
                    ->orWhere('estado_aprobacion', 'aprobada');
            })
            ->get()
            ->each(function ($salida) {
                foreach ($salida->detalles as $detalle) {
                    $loteId = $detalle->lote_id ?: $this->resolverLoteProducto($detalle->producto_id);
                    if (!$loteId) continue;

                    if (!$detalle->lote_id) {
                        $detalle->lote_id = $loteId;
                        $detalle->save();
                    }

                    $distribucion = Distribucion::firstOrNew([
                        'salida_detalle_id' => $detalle->id_detalle_salida,
                    ]);

                    $distribucion->fill([
                        'salida_id' => $salida->id_salida,
                        'lote_id' => $loteId,
                        'destino_id' => $salida->destino_id,
                        'responsable_id' => $salida->distribuidor_id,
                        'fecha_envio' => $salida->fecha_hora ?: now(),
                        'cantidad_productos' => $detalle->cantidad,
                    ]);

                    if (!$distribucion->exists) {
                        $distribucion->estado = 'designado';
                        $distribucion->observaciones = $salida->observaciones;
                    }

                    $distribucion->save();
                }
            });
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
}
