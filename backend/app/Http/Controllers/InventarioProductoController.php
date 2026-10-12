<?php

namespace App\Http\Controllers;

use App\Models\InventarioProducto;
use App\Models\Producto;
use App\Services\LoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->query($request, true)
                ->orderByDesc('fecha_registro')
                ->limit($this->limit($request))
                ->get()
        );
    }

    public function inactivos(Request $request): JsonResponse
    {
        return response()->json(
            $this->query($request, false)
                ->orderByDesc('fecha_registro')
                ->limit($this->limit($request))
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'producto_id' => 'required|exists:productos,id_producto',
            'lote_id' => 'nullable|exists:lotes,id_lote',
            'clasificacion' => 'nullable|string|max:20',
            'cantidad' => 'required|numeric|min:0.001',
            'ruta_evidencia' => 'nullable|string|max:300',
        ]);

        $inventario = DB::transaction(function () use ($data) {
            $producto = Producto::findOrFail($data['producto_id']);
            $stockAnterior = (float) (InventarioProducto::where('producto_id', $data['producto_id'])
                ->orderByDesc('id_inventario')
                ->value('stock') ?? 0);
            $cantidad = (float) $data['cantidad'];
            $clasificacion = $data['clasificacion'] ?? 'ajuste';
            $stockNuevo = $clasificacion === 'salida'
                ? $stockAnterior - $cantidad
                : $stockAnterior + $cantidad;

            if ($stockNuevo < 0) {
                abort(422, 'Stock insuficiente para registrar la salida.');
            }

            $loteId = $data['lote_id'] ?? null;

            if ($producto->tipo_producto === 'Producto Terminado' && $clasificacion !== 'salida') {
                $lote = app(LoteService::class)->crearLoteIngresoProductoTerminado($producto, $cantidad);
                $loteId = $lote?->id_lote;
            }

            return InventarioProducto::create([
                'producto_id' => $data['producto_id'],
                'lote_id' => $loteId,
                'clasificacion' => $clasificacion,
                'cantidad' => $cantidad,
                'stock' => $stockNuevo,
                'ruta_evidencia' => $data['ruta_evidencia'] ?? null,
                'fecha_registro' => now(),
                'activo' => true,
            ]);
        });

        return response()->json($inventario->load(['producto', 'lote']), 201);
    }

    public function destroy(InventarioProducto $inventarioProducto): JsonResponse
    {
        DB::transaction(function () use ($inventarioProducto) {
            $inventarioProducto->update(['activo' => false]);
            if ($inventarioProducto->lote_id) {
                $inventarioProducto->lote()->update(['estado' => 'inactivo']);
            }
        });

        return response()->json(['message' => 'Movimiento archivado']);
    }

    public function update(Request $request, InventarioProducto $inventarioProducto): JsonResponse
    {
        $data = $request->validate([
            'producto_id' => 'required|exists:productos,id_producto',
            'clasificacion' => 'required|string|in:ajuste,ingreso,salida,rechazado',
            'cantidad' => 'required|numeric|min:0.001',
        ]);

        $cantidadAnterior = (float) $inventarioProducto->cantidad;
        $cantidadNueva = (float) $data['cantidad'];
        $delta = $cantidadNueva - $cantidadAnterior;
        $signo = $data['clasificacion'] === 'salida' ? -1 : 1;

        $inventarioProducto->update([
            'producto_id' => $data['producto_id'],
            'clasificacion' => $data['clasificacion'],
            'cantidad' => $cantidadNueva,
            'stock' => max(0, (int) $inventarioProducto->stock + ($delta * $signo)),
        ]);

        return response()->json($inventarioProducto->fresh()->load(['producto', 'lote']));
    }

    public function activar(InventarioProducto $inventarioProducto): JsonResponse
    {
        DB::transaction(function () use ($inventarioProducto) {
            $inventarioProducto->update(['activo' => true]);
            if ($inventarioProducto->lote) {
                app(LoteService::class)->activar($inventarioProducto->lote);
            }
        });

        return response()->json($inventarioProducto->fresh()->load(['producto', 'lote']));
    }

    public function eliminarDefinitivo(InventarioProducto $inventarioProducto): JsonResponse
    {
        $inventarioProducto->delete();
        return response()->json(['message' => 'Movimiento eliminado permanentemente']);
    }

    private function query(Request $request, bool $activo)
    {
        $buscar = $request->query('buscar');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $tipo = $request->query('tipo_producto');
        $clasificacion = $request->query('clasificacion');

        return InventarioProducto::query()
            ->with(['producto', 'lote', 'ingresoDetalle'])
            ->where('activo', $activo)
            ->when($desde, fn ($q) => $q->whereDate('fecha_registro', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_registro', '<=', $hasta))
            ->when($tipo, fn ($q) => $q->whereHas('producto', fn ($p) => $p->where('tipo_producto', $tipo)))
            ->when($clasificacion, fn ($q) => $q->where('clasificacion', $clasificacion))
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('clasificacion', 'ILIKE', "%{$buscar}%")
                        ->orWhereHas('producto', function ($p) use ($buscar) {
                            $p->where('nombre', 'ILIKE', "%{$buscar}%")
                                ->orWhere('codigo', 'ILIKE', "%{$buscar}%")
                                ->orWhere('categoria', 'ILIKE', "%{$buscar}%")
                                ->orWhere('tipo_producto', 'ILIKE', "%{$buscar}%");
                        });
                });
            });
    }

    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 100), 1), 500);
    }
}
