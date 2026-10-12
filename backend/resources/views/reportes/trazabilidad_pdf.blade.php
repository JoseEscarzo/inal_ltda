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
  <h1>Reporte de trazabilidad</h1>
  <table>
    <thead>
      <tr>
        <th>ID</th><th>Lote</th><th>Producto</th><th>Destino</th><th>Responsable</th><th>Cantidad</th><th>Estado</th><th>Fecha envio</th><th>Observaciones</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($registros as $d)
        <tr>
          <td>{{ $d->id_distribucion }}</td>
          <td>{{ $d->lote?->codigo_lote }}</td>
          <td>{{ $d->lote?->producto?->nombre }}</td>
          <td>{{ $d->destino?->nombre }}</td>
          <td>{{ $d->responsable?->name }}</td>
          <td>{{ $d->cantidad_productos }}</td>
          <td>{{ $d->estado }}</td>
          <td>{{ optional($d->fecha_envio)->format('Y-m-d H:i') }}</td>
          <td>{{ $d->observaciones }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
