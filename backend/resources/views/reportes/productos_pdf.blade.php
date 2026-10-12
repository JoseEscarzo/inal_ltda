<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Productos</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 30px;
            color: #333;
        }

        .titulo {
            text-align: center;
            margin-bottom: 25px;
        }

        .titulo h1 {
            margin: 0;
            color: #2B2353;
            font-size: 26px;
        }

        .titulo p {
            margin-top: 5px;
            color: #777;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #2B2353;
            color: white;
        }

        th {
            padding: 12px;
            border: 1px solid #ddd;
            font-size: 13px;
            text-align: left;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 11px;
            color: #777;
        }
    </style>
</head>

<body>

    <div class="titulo">
        <h1>Reporte de Productos</h1>
        <p>Sistema INAL Ltda</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Categoría</th>
                <th>Tipo</th>
                <th>Presentación</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($productos as $producto)
                <tr>
                    <td>{{ $producto->id_producto }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->codigo }}</td>
                    <td>{{ $producto->descripcion }}</td>
                    <td>{{ $producto->categoria }}</td>
                    <td>{{ $producto->tipo_producto }}</td>
                    <td>{{ $producto->presentacion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

</body>

</html>
