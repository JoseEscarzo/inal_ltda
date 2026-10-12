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
  <h1>Reporte de lineas de produccion</h1>
  <table>
    <thead>
      <tr>
        <th>ID</th><th>Linea</th><th>Turno</th><th>Estado</th><th>Lotes</th><th>Descripcion</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($lineas as $linea)
        <tr>
          <td>{{ $linea->id_linea }}</td>
          <td>{{ $linea->nombre }}</td>
          <td>{{ $linea->turno }}</td>
          <td>{{ $linea->estado }}</td>
          <td>{{ $linea->lotes_count }}</td>
          <td>{{ $linea->descripcion }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
