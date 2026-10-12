<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $movimientosPorLote = DB::table('inventario_productos as i')
            ->join('productos as p', 'p.id_producto', '=', 'i.producto_id')
            ->join('lotes as l', 'l.id_lote', '=', 'i.lote_id')
            ->where('i.activo', true)
            ->where('p.tipo_producto', 'Producto Terminado')
            ->whereIn('i.clasificacion', ['ingreso', 'ajuste'])
            ->orderBy('i.id_inventario')
            ->get([
                'i.id_inventario',
                'i.producto_id',
                'i.lote_id',
                'i.cantidad',
                'i.fecha_registro',
            ])
            ->groupBy('lote_id');

        foreach ($movimientosPorLote as $movimientos) {
            if ($movimientos->count() <= 1) {
                continue;
            }

            foreach ($movimientos->slice(1) as $movimiento) {
                $fecha = \Carbon\Carbon::parse($movimiento->fecha_registro);
                $codigo = $this->generarCodigo($fecha);

                $nuevoLoteId = DB::table('lotes')->insertGetId([
                    'codigo_lote' => $codigo,
                    'producto_id' => $movimiento->producto_id,
                    'linea_id' => null,
                    'usuario_id' => null,
                    'cantidad' => $movimiento->cantidad,
                    'fecha_inicio' => $fecha,
                    'fecha_fin' => $fecha->copy()->addMonthsNoOverflow(4),
                    'turno' => null,
                    'estado' => 'activo',
                    'observaciones' => 'Lote separado automaticamente desde movimiento de inventario existente.',
                ], 'id_lote');

                DB::table('inventario_productos')
                    ->where('id_inventario', $movimiento->id_inventario)
                    ->update(['lote_id' => $nuevoLoteId]);
            }
        }
    }

    public function down(): void
    {
        //
    }

    private function generarCodigo(\Carbon\Carbon $fecha): string
    {
        $year = $fecha->format('Y');
        $ultimo = DB::table('lotes')
            ->where('codigo_lote', 'ILIKE', "L-{$year}-%")
            ->orderByDesc('id_lote')
            ->value('codigo_lote');

        $numero = $ultimo && preg_match('/L-'.$year.'-(\d+)/', $ultimo, $m)
            ? ((int) $m[1]) + 1
            : 1;

        return 'L-'.$year.'-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
    }
};
