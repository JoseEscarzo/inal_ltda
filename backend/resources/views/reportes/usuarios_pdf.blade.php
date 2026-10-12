<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Usuarios</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #2B2353; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2B2353; color: white; text-align: left; padding: 8px; }
        td { border: 1px solid #e5e7eb; padding: 7px; }
        tbody tr:nth-child(even) { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Reporte de Usuarios</h1>
    <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nombre</th><th>Correo</th><th>Celular</th><th>Nro Carnet</th><th>Fecha nacimiento</th><th>Rol</th><th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->id }}</td>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->phone }}</td>
                    <td>{{ $usuario->nro_carnet }}</td>
                    <td>{{ optional($usuario->fecha_nacimiento)->format('Y-m-d') }}</td>
                    <td>{{ $usuario->rol?->nombre }}</td>
                    <td>{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">Sin usuarios</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
