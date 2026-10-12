<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Inventario</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #2B2353; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2B2353; color: white; text-align: left; padding: 8px; }
        td { border-bottom: 1px solid #e5e7eb; padding: 7px; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h1>Reporte de Inventario</h1>
    <div class="meta">Generado: {{ now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Codigo</th>
                <th>Tipo</th>
                <th>Movimiento</th>
                <th class="num">Cantidad</th>
                <th class="num">Stock</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inventarios as $item)
                <tr>
                    <td>{{ $item->id_inventario }}</td>
                    <td>{{ optional($item->fecha_registro)->format('Y-m-d H:i') }}</td>
                    <td>{{ $item->producto?->nombre ?? '-' }}</td>
                    <td>{{ $item->producto?->codigo ?? '-' }}</td>
                    <td>{{ $item->producto?->tipo_producto ?? '-' }}</td>
                    <td>{{ $item->clasificacion ?? '-' }}</td>
                    <td class="num">{{ $item->cantidad }}</td>
                    <td class="num">{{ $item->stock }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">Sin movimientos</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
