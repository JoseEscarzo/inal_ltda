<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    h1 { font-size: 20px; margin: 0 0 12px; color: #2B2353; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #2B2353; color: white; padding: 8px; text-align: left; }
    td { border: 1px solid #e5e7eb; padding: 7px; }
  </style>
</head>
<body>
  <h1>Reporte de lotes</h1>
  <table>
    <thead>
      <tr>
        <th>Lote</th><th>Producto</th><th>Cantidad</th><th>Produccion</th><th>Vencimiento</th><th>Estado</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($lotes as $lote)
        <tr>
          <td>{{ $lote->codigo_lote }}</td>
          <td>{{ $lote->producto?->nombre }}</td>
          <td>{{ $lote->cantidad }}</td>
          <td>{{ optional($lote->fecha_inicio)->format('Y-m-d') }}</td>
          <td>{{ optional($lote->fecha_fin)->format('Y-m-d') }}</td>
          <td>{{ $lote->estado }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
