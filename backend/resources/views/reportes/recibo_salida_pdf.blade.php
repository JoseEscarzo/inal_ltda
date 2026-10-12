<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $salida->nro_informe }}</title>
    <style>
        @page { margin: 10mm; size: 216mm 140mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
        }
        .receipt {
            width: 196mm;
            min-height: 120mm;
            border: 2px solid #1f3f99;
            border-radius: 6px;
            padding: 7mm;
        }
        .header {
            display: table;
            width: 100%;
        }
        .brand,
        .date-box {
            display: table-cell;
            vertical-align: top;
        }
        .title {
            color: #1f3f99;
            font-size: 30px;
            font-style: italic;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 0 0 2mm;
        }
        .date-box {
            width: 42mm;
            text-align: right;
        }
        table.date {
            width: 38mm;
            margin-left: auto;
            border-collapse: collapse;
            text-align: center;
            color: #111827;
        }
        .date th {
            background: #1f3f99;
            color: white;
            font-size: 9px;
            padding: 2px;
        }
        .date td {
            border: 1px solid #1f3f99;
            padding: 4px 2px;
            font-size: 11px;
        }
        .number {
            color: #1f3f99;
            font-weight: 800;
            margin-top: 2mm;
            font-size: 12px;
        }
        .line {
            margin-top: 3mm;
            font-size: 13px;
        }
        .label {
            font-style: italic;
            margin-right: 2mm;
        }
        .value {
            display: inline-block;
            min-width: 85mm;
            border-bottom: 1px dotted #4b5563;
            padding-left: 2mm;
        }
        .concept {
            margin: 3mm 0 2mm;
            font-size: 13px;
            font-style: italic;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border: 1px solid #1f3f99;
            border-radius: 4px;
        }
        .items th {
            background: #1f3f99;
            color: white;
            text-align: left;
            padding: 4px 8px;
            font-size: 11px;
        }
        .items td {
            border-bottom: 1px solid #d1d5db;
            padding: 5px 8px;
            font-size: 11px;
        }
        .items tr:last-child td {
            border-bottom: 0;
        }
        .observations {
            margin-top: 4mm;
            font-size: 12px;
        }
        .observations span {
            display: inline-block;
            width: 92mm;
            border-bottom: 1px dotted #4b5563;
            margin-left: 2mm;
        }
        .signatures {
            display: table;
            width: 100%;
            margin-top: 13mm;
        }
        .signature {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 6mm;
        }
        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 2mm;
            color: #1f3f99;
            font-weight: 900;
            letter-spacing: .5px;
            font-size: 11px;
        }
        .signature small {
            display: block;
            margin-top: 2mm;
            color: #4b5563;
            font-size: 9px;
        }
    </style>
</head>
<body>
@php
    $fecha = $salida->aprobado_en ?: $salida->fecha_hora ?: now();
@endphp
<div class="receipt">
    <div class="header">
        <div class="brand">
            <h1 class="title">RECIBO</h1>
            <div>Inal Ltda. Helados Panda</div>
            <div>Carretera a Viacha, Km 8, N° 777 — La Paz</div>
        </div>
        <div class="date-box">
            <table class="date">
                <tr>
                    <th>DIA</th>
                    <th>MES</th>
                    <th>AÑO</th>
                </tr>
                <tr>
                    <td>{{ $fecha->format('d') }}</td>
                    <td>{{ $fecha->format('m') }}</td>
                    <td>{{ $fecha->format('Y') }}</td>
                </tr>
            </table>
            <div class="number">N° {{ $salida->nro_informe }}</div>
        </div>
    </div>

    <div class="line">
        <span class="label">Entregué a:</span>
        <span class="value">{{ $salida->destino?->nombre ?: 'Destino asignado' }}</span>
    </div>
    <div class="line">
        <span class="label">Distribuidor:</span>
        <span class="value">{{ $salida->distribuidor?->name ?: 'Distribuidor asignado' }}</span>
    </div>

    <div class="concept">Por concepto de entrega de:</div>

    <table class="items">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Lote</th>
                <th>Cantidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salida->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto?->nombre ?: 'Producto' }}</td>
                    <td>{{ $detalle->lote?->codigo_lote ?: '-' }}</td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>Aprobado</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="observations">
        <em>Observaciones:</em><span>{{ $salida->observaciones }}</span>
    </div>

    <div class="signatures">
        <div class="signature">
            <div class="signature-line">ENTREGUÉ CONFORME</div>
            <small>{{ $salida->distribuidor?->name ?: 'Distribuidor' }} — Distribuidor</small>
        </div>
        <div class="signature">
            <div class="signature-line">RECIBÍ CONFORME</div>
            <small>{{ $salida->destino?->nombre ?: 'Cliente' }} — Cliente</small>
        </div>
    </div>
</div>
</body>
</html>
