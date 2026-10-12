<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Distribuciones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #2B2353; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2B2353; color: white; text-align: left; padding: 8px; }
        td { border: 1px solid #e5e7eb; padding: 7px; }
        tbody tr:nth-child(even) { background: #f5f5f5; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h1>Reporte de Distribuciones</h1>
    <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                @foreach ($columnas as $key => $label)
                    <th class="{{ in_array($key, ['entradas', 'salidas', 'total']) ? 'num' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr>
                    @foreach ($columnas as $key => $label)
                        <td class="{{ in_array($key, ['entradas', 'salidas', 'total']) ? 'num' : '' }}">
                            {{ $registro[$key] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columnas) }}" style="text-align:center;">Sin registros</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
