<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verificacion de factura</title>
  <style>
    body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, sans-serif; background: #f4f7fb; color: #111827; }
    .top { background: #073763; color: white; padding: 22px; font-size: 28px; font-weight: 800; letter-spacing: 1px; }
    .card { max-width: 520px; margin: 36px auto; background: white; border-radius: 18px; box-shadow: 0 18px 40px rgba(15, 23, 42, .12); overflow: hidden; }
    .head { padding: 28px; border-bottom: 1px solid #e5e7eb; text-align: center; }
    h1 { margin: 0; font-size: 24px; color: #172554; }
    .body { padding: 24px 28px; }
    .row { display: grid; grid-template-columns: 150px 1fr; gap: 12px; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
    .label { color: #64748b; font-weight: 700; }
    .ok { display: inline-block; padding: 8px 14px; border-radius: 999px; background: #dcfce7; color: #047857; font-weight: 800; }
  </style>
</head>
<body>
  <div class="top">SIAT DEMO - INAL</div>
  <div class="card">
    <div class="head">
      <h1>Verificacion de Factura Electronica</h1>
    </div>
    <div class="body">
      <div class="row"><div class="label">Factura</div><div>{{ str_pad($factura->numero_factura, 6, '0', STR_PAD_LEFT) }}</div></div>
      <div class="row"><div class="label">Codigo</div><div>{{ $factura->codigo_qr }}</div></div>
      <div class="row"><div class="label">Fecha emision</div><div>{{ optional($factura->fecha_emision)->format('d/m/Y H:i:s') }}</div></div>
      <div class="row"><div class="label">Monto total</div><div>{{ number_format((float) $factura->total_pagar, 2) }} Bs</div></div>
      <div class="row"><div class="label">Estado</div><div><span class="ok">{{ strtoupper($factura->estado) }}</span></div></div>
      <div class="row"><div class="label">NIT emisor</div><div>{{ $nitEmpresa }}</div></div>
      <div class="row"><div class="label">Razon social</div><div>INAL Ltda. Helados Panda</div></div>
      <div class="row"><div class="label">Cliente</div><div>{{ $factura->cliente?->razon_social ?? '-' }}</div></div>
    </div>
  </div>
</body>
</html>
