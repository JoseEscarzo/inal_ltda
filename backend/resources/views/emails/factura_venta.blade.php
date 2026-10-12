<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
</head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,sans-serif;color:#111827;">
  @php
    $cliente = $factura->cliente;
    $nombreCliente = $cliente?->nombre_encargado ?: ($cliente?->razon_social ?? 'cliente');
  @endphp
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:94%;background:#111;border:1px solid #222;">
          <tr>
            <td style="background:#5b45a1;color:#fff;text-align:center;padding:28px 24px;">
              <div style="width:62px;height:62px;border-radius:50%;background:#fff;color:#5b45a1;margin:0 auto 14px;font-size:40px;line-height:62px;font-weight:800;">✓</div>
              <div style="font-size:28px;line-height:1.2;font-weight:800;">Su factura se ha emitido<br>con exito</div>
            </td>
          </tr>
          <tr>
            <td style="padding:34px 42px;color:#fff;font-size:16px;line-height:1.45;">
              <p style="margin:0 0 22px;">Estimado cliente,</p>
              <p style="margin:0 0 22px;">
                Su factura para <strong>{{ strtoupper($nombreCliente) }}</strong> se ha emitido con exito.
              </p>
              <p style="margin:0 0 20px;">
                Codigo de autorizacion:<br>
                <strong style="word-break:break-all;">{{ $factura->codigo_autorizacion }}</strong>
              </p>
              <p style="margin:0 0 18px;">Nro de factura: <strong>{{ str_pad($factura->numero_factura, 6, '0', STR_PAD_LEFT) }}</strong></p>
              <p style="margin:0 0 18px;">Fecha Emision: <strong>{{ optional($factura->fecha_emision)->format('d/m/Y H:i:s') }}</strong></p>
              <p style="margin:0 0 28px;">Monto: <strong>{{ number_format((float) $factura->total_pagar, 2) }}</strong></p>
              <a href="{{ $factura->url_qr }}" style="display:block;background:#5b45a1;color:#fff;text-decoration:none;text-align:center;border:1px solid #c4b5fd;border-radius:5px;padding:18px 16px;font-size:22px;line-height:1.5;">
                Verifique su factura haciendo click aqui
              </a>
            </td>
          </tr>
          <tr>
            <td style="background:#202020;height:28px;"></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
