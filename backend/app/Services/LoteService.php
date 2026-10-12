<?php

namespace App\Services;

use App\DTOs\LoteDTO;
use App\Models\InventarioProducto;
use App\Models\Lote;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoteService
{
    public function listar(int $perPage = 15)
    {
        $this->asegurarLotesProductosTerminados();
        $this->sincronizarEstadosPorFecha();

        return Lote::with(['producto.receta.materiaPrima', 'linea', 'usuario', 'distribuciones.destino'])
            ->orderByDesc('fecha_inicio')
            ->paginate($perPage);
    }

    public function crear(LoteDTO $dto): Lote
    {
        return DB::transaction(function () use ($dto) {
            $fechaInicio = $dto->fecha_inicio ? Carbon::parse($dto->fecha_inicio) : now();
            $lote = Lote::create([
                'codigo_lote'  => $dto->codigo_lote ?: $this->generarCodigo($fechaInicio),
                'producto_id'  => $dto->producto_id,
                'linea_id'     => $dto->linea_id,
                'usuario_id'   => $dto->usuario_id,
                'cantidad'     => $dto->cantidad ?? 0,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $dto->fecha_fin ? Carbon::parse($dto->fecha_fin) : $fechaInicio->copy()->addMonthsNoOverflow(4),
                'turno'        => $dto->turno,
                'estado'       => $this->estadoPorFecha($dto->estado ?: 'activo', $fechaInicio, $dto->fecha_fin ? Carbon::parse($dto->fecha_fin) : $fechaInicio->copy()->addMonthsNoOverflow(4)),
                'observaciones' => $dto->observaciones,
            ]);

            $this->registrarInventario($lote);

            return $lote->fresh()->load(['producto.receta.materiaPrima', 'linea', 'usuario', 'distribuciones.destino']);
        });
    }

    public function actualizar(Lote $lote, LoteDTO $dto): Lote
    {
        $lote->update([
            'codigo_lote'  => $dto->codigo_lote,
            'producto_id'  => $dto->producto_id,
            'linea_id'     => $dto->linea_id,
            'usuario_id'   => $dto->usuario_id,
            'cantidad'     => $dto->cantidad ?? 0,
            'fecha_inicio' => $dto->fecha_inicio,
            'fecha_fin'    => $dto->fecha_fin,
            'turno'        => $dto->turno,
            'estado'       => $this->estadoPorFecha($dto->estado, $dto->fecha_inicio, $dto->fecha_fin),
            'observaciones' => $dto->observaciones,
        ]);

        $this->sincronizarInventario($lote->fresh());

        return $lote->fresh()->load(['producto.receta.materiaPrima', 'linea', 'usuario', 'distribuciones.destino']);
    }

    public function eliminar(Lote $lote): void
    {
        $lote->update(['estado' => 'inactivo']);
    }

    public function activar(Lote $lote): Lote
    {
        $lote->update([
            'estado' => $this->estadoPorFecha('activo', $lote->fecha_inicio, $lote->fecha_fin),
        ]);

        return $lote->fresh()->load(['producto.receta.materiaPrima', 'linea', 'usuario', 'distribuciones.destino']);
    }

    private function generarCodigo(Carbon $fecha): string
    {
        $year = $fecha->format('Y');
        $ultimo = Lote::where('codigo_lote', 'ILIKE', "L-{$year}-%")
            ->orderByDesc('id_lote')
            ->value('codigo_lote');

        $numero = $ultimo && preg_match('/L-'.$year.'-(\d+)/', $ultimo, $m)
            ? ((int) $m[1]) + 1
            : 1;

        return 'L-'.$year.'-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
    }

    public function asegurarLoteProductoTerminado(Producto $producto): ?Lote
    {
        if ($producto->tipo_producto !== 'Producto Terminado') return null;

        $existente = Lote::where('producto_id', $producto->id_producto)->first();
        if ($existente) return $existente;

        $fecha = $producto->created_at ? Carbon::parse($producto->created_at) : now();

        return Lote::create([
            'codigo_lote' => $this->generarCodigo($fecha),
            'producto_id' => $producto->id_producto,
            'linea_id' => null,
            'usuario_id' => null,
            'cantidad' => 0,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha->copy()->addMonthsNoOverflow(4),
            'turno' => null,
            'estado' => $this->estadoPorFecha('activo', $fecha, $fecha->copy()->addMonthsNoOverflow(4)),
            'observaciones' => 'Lote generado automaticamente desde producto terminado.',
        ]);
    }

    public function crearLoteIngresoProductoTerminado(Producto $producto, float $cantidad, ?Carbon $fecha = null): ?Lote
    {
        if ($producto->tipo_producto !== 'Producto Terminado') return null;

        $fechaProduccion = $fecha ?: now();

        return Lote::create([
            'codigo_lote' => $this->generarCodigo($fechaProduccion),
            'producto_id' => $producto->id_producto,
            'linea_id' => null,
            'usuario_id' => null,
            'cantidad' => $cantidad,
            'fecha_inicio' => $fechaProduccion,
            'fecha_fin' => $fechaProduccion->copy()->addMonthsNoOverflow(4),
            'turno' => null,
            'estado' => $this->estadoPorFecha('activo', $fechaProduccion, $fechaProduccion->copy()->addMonthsNoOverflow(4)),
            'observaciones' => 'Lote generado automaticamente por ingreso de inventario.',
        ]);
    }

    private function asegurarLotesProductosTerminados(): void
    {
        Producto::where('activo', true)
            ->where('tipo_producto', 'Producto Terminado')
            ->orderBy('id_producto')
            ->get()
            ->each(fn ($producto) => $this->asegurarLoteProductoTerminado($producto));
    }

    private function registrarInventario(Lote $lote): void
    {
        if (!$lote->producto_id || $lote->cantidad <= 0) return;

        $stockAnterior = (float) (InventarioProducto::where('producto_id', $lote->producto_id)
            ->orderByDesc('id_inventario')
            ->value('stock') ?? 0);

        InventarioProducto::create([
            'producto_id' => $lote->producto_id,
            'lote_id' => $lote->id_lote,
            'clasificacion' => 'ingreso',
            'cantidad' => $lote->cantidad,
            'stock' => $stockAnterior + (float) $lote->cantidad,
            'fecha_registro' => now(),
            'activo' => true,
        ]);
    }

    private function sincronizarInventario(Lote $lote): void
    {
        $movimiento = InventarioProducto::where('lote_id', $lote->id_lote)
            ->where('producto_id', $lote->producto_id)
            ->orderByDesc('id_inventario')
            ->first();

        if (!$movimiento) {
            $this->registrarInventario($lote);
            return;
        }

        $movimiento->update([
            'cantidad' => $lote->cantidad,
            'clasificacion' => 'ingreso',
        ]);
    }

    public function sincronizarEstadosPorFecha(): void
    {
        Lote::query()
            ->whereNotIn('estado', ['inactivo', 'cerrado', 'incidencia'])
            ->get()
            ->each(function (Lote $lote) {
                $estado = $this->estadoPorFecha($lote->estado ?: 'activo', $lote->fecha_inicio, $lote->fecha_fin);
                if ($lote->estado !== $estado) {
                    $lote->update(['estado' => $estado]);
                }
            });
    }

    private function estadoPorFecha(?string $estadoActual, $fechaInicio, $fechaFin): string
    {
        if (in_array($estadoActual, ['inactivo', 'cerrado', 'incidencia'], true)) {
            return $estadoActual;
        }

        if (!$fechaFin) {
            return 'activo';
        }

        $vencimiento = Carbon::parse($fechaFin)->startOfDay();
        $hoy = now()->startOfDay();

        if ($vencimiento->lessThanOrEqualTo($hoy)) {
            return 'vencido';
        }

        if ($hoy->diffInDays($vencimiento, false) <= 30) {
            return 'por vencer';
        }

        return 'activo';
    }
}
