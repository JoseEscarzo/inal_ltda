<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 10px; }
    body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 8.6px; }
    .center { text-align: center; }
    .logo { width: 62px; margin-bottom: 3px; }
    h1 { margin: 2px 0; font-size: 15px; letter-spacing: 2px; }
    .line { border-top: 1px dashed #000; margin: 7px 0; }
    .label { font-weight: 700; text-transform: uppercase; }
    .value { font-weight: 700; }
    table { width: 100%; border-collapse: collapse; }
    th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 3px; font-size: 8px; text-align: left; }
    td { padding: 4px 3px; border-bottom: 1px solid #ddd; vertical-align: top; }
    .num { text-align: right; }
    .totals td { border: 0; padding: 3px 0; }
    .total { font-size: 11.5px; font-weight: 800; }
    .qr { text-align: center; margin-top: 8px; }
    .qr img { width: 84px; height: 84px; }
    .small { font-size: 7.4px; line-height: 1.35; }
  </style>
</head>
<body>
  @php
    $destino = $factura->salida?->destino;
    $cliente = $factura->cliente;
  @endphp
  <div class="center">
    @if(!empty($logoImageUrl))
      <img class="logo" src="{{ $logoImageUrl }}" alt="Helados Panda">
    @endif
    <h1>FACTURA</h1>
    <strong>INAL Ltda. Helados Panda</strong><br>
    NIT: {{ $nitEmpresa }}<br>
    Factura Nro. {{ str_pad($factura->numero_factura, 6, '0', STR_PAD_LEFT) }}<br>
    Autorizacion: {{ $factura->codigo_autorizacion }}<br>
    {{ optional($factura->fecha_emision)->format('d/m/Y H:i') }}
  </div>

  <div class="line"></div>
  <div><span class="label">Cliente:</span> <span class="value">{{ $cliente?->razon_social ?? 'Cliente' }}</span></div>
  <div><span class="label">Encargado:</span> <span class="value">{{ $cliente?->nombre_encargado ?: '-' }}</span></div>
  <div><span class="label">NIT/CI:</span> <span class="value">{{ $cliente?->nit_ci ?: '0' }}</span></div>
  <div><span class="label">Tienda:</span> <span class="value">{{ $destino?->nombre ?? '-' }}</span></div>
  <div><span class="label">Distribuidor:</span> <span class="value">{{ $factura->distribuidor?->name ?? '-' }}</span></div>

  <div class="line"></div>
  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th>Lote</th>
        <th class="num">Cant.</th>
        <th class="num">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($factura->detalles as $detalle)
        <tr>
          <td>{{ $detalle->producto?->nombre ?? 'Producto' }}<br><span class="small">{{ number_format((float) $detalle->precio_venta, 2) }} Bs/u</span></td>
          <td>{{ $detalle->lote?->codigo_lote ?? '-' }}</td>
          <td class="num">{{ number_format((float) $detalle->cantidad, 0) }}</td>
          <td class="num">{{ number_format((float) $detalle->subtotal, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotal</td><td class="num">{{ number_format((float) $factura->subtotal, 2) }} Bs</td></tr>
    <tr><td>Descuento</td><td class="num">{{ number_format((float) $factura->descuento, 2) }} Bs</td></tr>
    <tr><td class="total">TOTAL</td><td class="num total">{{ number_format((float) $factura->total_pagar, 2) }} Bs</td></tr>
  </table>

  <div class="qr">
    <img src="{{ $qrImageUrl }}" alt="QR factura">
    <div class="small">Verificacion interna INAL</div>
  </div>

  <div class="line"></div>
  <div class="center small">
    Factura demo. Para validez fiscal real se requiere integracion SIAT.
  </div>
</body>
</html>
