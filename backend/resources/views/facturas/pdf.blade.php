<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 26px 30px; }
    body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10.5px; }
    .top { width: 100%; }
    .emisor { width: 36%; vertical-align: top; }
    .logo-cell { width: 28%; text-align: center; vertical-align: top; }
    .meta { width: 36%; vertical-align: top; }
    .logo { width: 118px; height: auto; margin-top: 0; margin-bottom: 4px; }
    h1 { margin: 10px 0 0; font-size: 19px; text-align: center; letter-spacing: .5px; }
    .subtitle { text-align: center; margin-top: 2px; font-size: 10px; }
    .bold { font-weight: 700; }
    .meta table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 2px 0 2px 8px; vertical-align: top; }
    .meta td:first-child { width: 38%; font-weight: 700; }
    .client { margin-top: 22px; width: 100%; border-collapse: collapse; }
    .client td { padding: 2px 10px 2px 0; vertical-align: top; }
    .client .label { width: 17%; font-weight: 700; }
    .client .value { width: 33%; }
    .line { border-top: 1px solid #000; margin: 12px 0 0; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .items th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 4px; font-size: 8.5px; text-transform: uppercase; text-align: left; }
    .items td { padding: 7px 4px; vertical-align: top; }
    .items .num { text-align: right; }
    .bottom-line { border-top: 1px solid #000; margin-top: 12px; }
    .amount-text { width: 52%; float: left; margin-top: 18px; font-size: 9px; }
    .totals { width: 34%; float: right; margin-top: 8px; border-collapse: collapse; }
    .totals td { padding: 4px 0; }
    .totals td:first-child { font-weight: 700; }
    .totals td:last-child { text-align: right; }
    .qr { width: 150px; float: right; clear: both; margin-top: 18px; text-align: center; }
    .qr img { width: 118px; height: 118px; }
    .legal { clear: both; margin-top: 18px; font-size: 8.7px; line-height: 1.45; }
    .clear { clear: both; }
  </style>
</head>
<body>
  @php
    $destino = $factura->salida?->destino;
    $cliente = $factura->cliente;
  @endphp

  <table class="top">
    <tr>
      <td class="emisor">
        <div class="bold">INAL LTDA. HELADOS PANDA</div>
        <div>CASA MATRIZ</div>
        <div>Carretera a Viacha, Km 8</div>
        <div>Nro. 777</div>
        <div>La Paz</div>
        <div>Telefono: 2487744</div>
      </td>
      <td class="logo-cell">
        @if(!empty($logoImageUrl))
          <img class="logo" src="{{ $logoImageUrl }}" alt="Helados Panda">
        @endif
        <h1>FACTURA</h1>
        <div class="subtitle">(Con Derecho a Credito Fiscal)</div>
      </td>
      <td class="meta">
        <table>
          <tr><td>NIT</td><td>{{ $nitEmpresa }}</td></tr>
          <tr><td>Factura Nro.</td><td>{{ str_pad($factura->numero_factura, 6, '0', STR_PAD_LEFT) }}</td></tr>
          <tr><td>Cod. Autorizacion</td><td>{{ $factura->codigo_autorizacion }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <table class="client">
    <tr>
      <td class="label">Fecha:</td>
      <td class="value">{{ optional($factura->fecha_emision)->format('d/m/Y H:i:s') }}</td>
      <td class="label">NIT/CI/CEX:</td>
      <td class="value">{{ $cliente?->nit_ci ?: '0' }}</td>
    </tr>
    <tr>
      <td class="label">Nombre/Razon Social:</td>
      <td class="value">{{ $cliente?->razon_social ?? 'Cliente' }}</td>
      <td class="label">Codigo Cliente:</td>
      <td class="value">{{ $cliente?->nit_ci ?: str_pad((string) $cliente?->id_cliente, 6, '0', STR_PAD_LEFT) }}</td>
    </tr>
    <tr>
      <td class="label">Encargado:</td>
      <td class="value">{{ $cliente?->nombre_encargado ?: '-' }}</td>
      <td class="label">Tienda:</td>
      <td class="value">{{ $destino?->nombre ?? '-' }}</td>
    </tr>
    <tr>
      <td class="label">Distribuidor:</td>
      <td class="value">{{ $factura->distribuidor?->name ?? '-' }}</td>
      <td class="label">Direccion:</td>
      <td class="value">{{ $cliente?->direccion ?: ($destino?->direccion ?? '-') }}</td>
    </tr>
  </table>

  <div class="line"></div>
  <table class="items">
    <thead>
      <tr>
        <th style="width:12%">Codigo</th>
        <th style="width:10%" class="num">Cantidad</th>
        <th style="width:13%">Unidad</th>
        <th>Descripcion</th>
        <th style="width:13%" class="num">Precio Unitario</th>
        <th style="width:12%" class="num">Descuento</th>
        <th style="width:13%" class="num">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($factura->detalles as $detalle)
        <tr>
          <td>{{ $detalle->producto?->codigo ?? 'PROD-'.$detalle->producto_id }}</td>
          <td class="num">{{ number_format((float) $detalle->cantidad, 0) }}</td>
          <td>{{ $detalle->producto?->presentacion ?: 'Unidad' }}</td>
          <td>
            {{ $detalle->producto?->nombre ?? 'Producto' }}
            @if($detalle->lote)
              - Lote {{ $detalle->lote->codigo_lote }}
            @endif
          </td>
          <td class="num">{{ number_format((float) $detalle->precio_venta, 2) }}</td>
          <td class="num">0.00</td>
          <td class="num">{{ number_format((float) $detalle->subtotal, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="bottom-line"></div>

  <div class="amount-text">
    Son: {{ number_format((float) $factura->total_pagar, 2) }} BOLIVIANOS
  </div>
  <table class="totals">
    <tr><td>SUB TOTAL Bs.</td><td>{{ number_format((float) $factura->subtotal, 2) }}</td></tr>
    <tr><td>DESCUENTO Bs.</td><td>{{ number_format((float) $factura->descuento, 2) }}</td></tr>
    <tr><td>TOTAL Bs.</td><td>{{ number_format((float) $factura->total_pagar, 2) }}</td></tr>
    <tr><td>MONTO A PAGAR Bs.</td><td>{{ number_format((float) $factura->total_pagar, 2) }}</td></tr>
    <tr><td>IMPORTE BASE CREDITO FISCAL Bs.</td><td>{{ number_format((float) $factura->total_pagar, 2) }}</td></tr>
  </table>
  <div class="clear"></div>

  <div class="qr">
    <img src="{{ $qrImageUrl }}" alt="QR factura">
  </div>
  <div class="clear"></div>

  <div class="legal">
    ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAIS. EL USO ILICITO SERA SANCIONADO PENALMENTE DE ACUERDO A LEY<br>
    Ley Nro. 453: Los productos deben suministrarse en condiciones de inocuidad, calidad y seguridad.<br>
    Este documento es la representacion grafica de una factura demo generada por el sistema INAL.
  </div>
</body>
</html>
