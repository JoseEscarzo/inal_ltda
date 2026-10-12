<?php

namespace App\Services;

use App\DTOs\ProductoDTO;
use App\Models\InventarioProducto;
use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoService
{
    // Solo activos
    public function listar(int $perPage = 100)
    {
        return Producto::with('receta.materiaPrima')
            ->where('activo', true)
            ->orderBy('nombre')
            ->paginate($perPage);
    }

    // Solo inactivos
    public function listarInactivos(int $perPage = 100)
    {
        return Producto::with('receta.materiaPrima')
            ->where('activo', false)
            ->orderBy('nombre')
            ->paginate($perPage);
    }

    public function crear(ProductoDTO $dto, array $receta = []): Producto
    {
        return DB::transaction(function () use ($dto, $receta) {
            $producto = Producto::create([
                'nombre'        => $dto->nombre,
                'codigo'        => $dto->codigo,
                'descripcion'   => $dto->descripcion,
                'categoria'     => $dto->categoria,
                'tipo_producto' => $dto->tipo_producto,
                'presentacion'  => $dto->presentacion,
                'precio_costo' => $dto->precio_costo,
                'precio_distribuidor' => $dto->precio_distribuidor,
                'precio_venta_publico' => $dto->precio_venta_publico,
                'activo'        => true,   // siempre activo al crear
                'created_at'     => now(),
            ]);

            if ($dto->tipo_producto === 'Producto Terminado' && !empty($receta)) {
                $this->sincronizarReceta($producto, $receta);
            }

            if ($dto->tipo_producto === 'Producto Terminado') {
                app(LoteService::class)->asegurarLoteProductoTerminado($producto);
            }

            return $producto->fresh()->load('receta.materiaPrima');
        });
    }

    public function actualizar(Producto $producto, ProductoDTO $dto, ?array $receta = null): Producto
    {
        return DB::transaction(function () use ($producto, $dto, $receta) {
            $producto->update([
                'nombre'        => $dto->nombre,
                'codigo'        => $dto->codigo,
                'descripcion'   => $dto->descripcion,
                'categoria'     => $dto->categoria,
                'tipo_producto' => $dto->tipo_producto,
                'presentacion'  => $dto->presentacion,
                'precio_costo' => $dto->precio_costo,
                'precio_distribuidor' => $dto->precio_distribuidor,
                'precio_venta_publico' => $dto->precio_venta_publico,
            ]);

            if ($receta !== null) {
                if ($dto->tipo_producto === 'Producto Terminado') {
                    $this->sincronizarReceta($producto, $receta);
                    app(LoteService::class)->asegurarLoteProductoTerminado($producto);
                } else {
                    $this->sincronizarReceta($producto, []);
                }
            }

            return $producto->fresh()->load('receta.materiaPrima');
        });
    }

    // Soft-delete: pone activo = false
    public function eliminar(Producto $producto): void
    {
        DB::transaction(function () use ($producto) {
            $producto->update(['activo' => false]);
            InventarioProducto::where('producto_id', $producto->id_producto)->update(['activo' => false]);
            Lote::where('producto_id', $producto->id_producto)->update(['estado' => 'inactivo']);
        });
    }

    // Reactiva: pone activo = true
    public function activar(Producto $producto): Producto
    {
        DB::transaction(function () use ($producto) {
            $producto->update(['activo' => true]);
            InventarioProducto::where('producto_id', $producto->id_producto)->update(['activo' => true]);
            Lote::where('producto_id', $producto->id_producto)
                ->where('estado', 'inactivo')
                ->update(['estado' => 'activo']);
            app(LoteService::class)->sincronizarEstadosPorFecha();
        });

        return $producto->fresh();
    }

    // Borra físicamente de la BD
    public function eliminarDefinitivo(Producto $producto): void
    {
        DB::transaction(function () use ($producto) {
            $productoId = $producto->id_producto;

            DB::table('detalle_factura')->where('producto_id', $productoId)->delete();
            DB::table('salida_detalles')->where('producto_id', $productoId)->delete();
            DB::table('ingreso_detalles')->where('producto_id', $productoId)->delete();
            DB::table('inventario_productos')->where('producto_id', $productoId)->delete();
            DB::table('producto_recetas')
                ->where('producto_id', $productoId)
                ->orWhere('materia_prima_id', $productoId)
                ->delete();
            DB::table('lotes')->where('producto_id', $productoId)->delete();

            $producto->delete();
        });
    }

    private function sincronizarReceta(Producto $producto, array $receta): void
    {
        $producto->load('receta');
        $anterior = $producto->receta
            ->groupBy('materia_prima_id')
            ->map(fn ($items) => (float) $items->sum('cantidad_total'));

        $nueva = collect($receta)
            ->filter(fn ($item) => isset($item['materia_prima_id'], $item['cantidad_total']))
            ->groupBy('materia_prima_id')
            ->map(fn ($items) => (float) collect($items)->sum('cantidad_total'));

        foreach ($anterior->keys()->merge($nueva->keys())->unique() as $materiaPrimaId) {
            $delta = round(($nueva[$materiaPrimaId] ?? 0) - ($anterior[$materiaPrimaId] ?? 0), 3);
            if ($delta > 0) {
                $this->registrarMovimientoMateriaPrima((int) $materiaPrimaId, $delta, 'salida');
            } elseif ($delta < 0) {
                $this->registrarMovimientoMateriaPrima((int) $materiaPrimaId, abs($delta), 'ajuste');
            }
        }

        $producto->receta()->delete();

        foreach ($receta as $item) {
            $producto->receta()->create([
                'materia_prima_id' => $item['materia_prima_id'],
                'cantidad_por_unidad' => $item['cantidad_por_unidad'],
                'cantidad_total' => $item['cantidad_total'],
                'unidades_producidas' => $item['unidades_producidas'] ?? 100,
                'unidad' => $item['unidad'] ?? null,
            ]);
        }
    }

    private function registrarMovimientoMateriaPrima(int $productoId, float $cantidad, string $clasificacion): void
    {
        $stockAnterior = (float) (InventarioProducto::where('producto_id', $productoId)
            ->orderByDesc('id_inventario')
            ->value('stock') ?? 0);

        $stockNuevo = $clasificacion === 'salida'
            ? $stockAnterior - $cantidad
            : $stockAnterior + $cantidad;

        if ($stockNuevo < 0) {
            $producto = Producto::find($productoId);
            throw ValidationException::withMessages([
                'receta' => [
                    'Stock insuficiente para '.$producto?->nombre.'. Stock actual: '.$stockAnterior.', requerido: '.$cantidad.'.',
                ],
            ]);
        }

        InventarioProducto::create([
            'producto_id' => $productoId,
            'clasificacion' => $clasificacion,
            'cantidad' => $cantidad,
            'stock' => $stockNuevo,
            'fecha_registro' => now(),
            'activo' => true,
        ]);
    }
}
