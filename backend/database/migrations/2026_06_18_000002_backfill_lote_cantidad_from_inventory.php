<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $productos = DB::table('productos')
            ->where('tipo_producto', 'Producto Terminado')
            ->pluck('id_producto');

        foreach ($productos as $productoId) {
            $totalLotes = (float) DB::table('lotes')
                ->where('producto_id', $productoId)
                ->sum('cantidad');

            if ($totalLotes > 0) {
                continue;
            }

            $stockActual = DB::table('inventario_productos')
                ->where('producto_id', $productoId)
                ->where('activo', true)
                ->orderByDesc('id_inventario')
                ->value('stock');

            if ($stockActual === null || (float) $stockActual <= 0) {
                continue;
            }

            $loteId = DB::table('lotes')
                ->where('producto_id', $productoId)
                ->orderBy('fecha_inicio')
                ->orderBy('id_lote')
                ->value('id_lote');

            if ($loteId) {
                DB::table('lotes')
                    ->where('id_lote', $loteId)
                    ->update(['cantidad' => $stockActual]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
