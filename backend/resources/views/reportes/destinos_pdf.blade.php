<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Destinos</title>
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
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 12px;
            text-align: left;
        }

        td {
            padding: 9px;
            border: 1px solid #ddd;
            font-size: 11px;
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
        <h1>Reporte de Destinos</h1>
        <p>Sistema INAL Ltda</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Ciudad</th>
                <th>Tipo</th>
                <th>Dirección</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($destinos as $destino)
                <tr>
                    <td>{{ $destino->id_destino }}</td>
                    <td>{{ $destino->nombre }}</td>
                    <td>{{ $destino->ciudad }}</td>
                    <td>{{ $destino->tipo }}</td>
                    <td>{{ $destino->direccion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

</body>

</html>
