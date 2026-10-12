<?php

namespace App\Services;

use App\Mail\FacturaVentaMail;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\SalidaRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FacturaService
{
    public function relaciones(): array
    {
        return [
            'cliente',
            'emitidoPor',
            'distribuidor',
            'salida.destino',
            'salida.despachadoPor',
            'detalles.producto',
            'detalles.lote',
            'pagos',
        ];
    }

    public function listar()
    {
        return Factura::with($this->relaciones())
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id_factura')
            ->paginate(25);
    }

    public function crearDesdeSalida(SalidaRevision $salida, User $usuario, array $opciones = []): Factura
    {
        return DB::transaction(function () use ($salida, $usuario, $opciones) {
            $salida->loadMissing([
                'detalles.producto',
                'detalles.lote',
                'destino.cliente',
                'distribuidor',
            ]);

            if ($salida->estado_aprobacion !== 'aprobada') {
                throw ValidationException::withMessages([
                    'salida_id' => ['La factura solo se puede generar cuando la salida esta aprobada.'],
                ]);
            }

            if ($salida->factura) {
                $factura = $salida->factura->load($this->relaciones());
                if (!empty($opciones['enviar_correo'])) {
                    $this->enviarPorCorreo($factura, $opciones['correo_destino'] ?? null);
                }
                return $factura;
            }

            $cliente = $this->resolverCliente($salida, $opciones['cliente_id'] ?? null);
            $numero = $this->siguienteNumeroFactura();
            $codigoQr = Str::upper(Str::random(12)).'-'.$numero;
            $codigoAutorizacion = Str::upper(str_replace('-', '', (string) Str::uuid()).Str::random(18));

            $factura = Factura::create([
                'salida_id' => $salida->id_salida,
                'cliente_id' => $cliente->id_cliente,
                'emitido_por_id' => $usuario->id,
                'distribuidor_id' => $salida->distribuidor_id,
                'numero_factura' => $numero,
                'codigo_autorizacion' => $codigoAutorizacion,
                'fecha_emision' => now(),
                'subtotal' => 0,
                'descuento' => (float) ($opciones['descuento'] ?? 0),
                'total_pagar' => 0,
                'estado' => 'emitida',
                'codigo_qr' => $codigoQr,
                'url_qr' => url('/api/v1/facturas/verificar/'.$codigoQr),
                'metodo_pago' => $opciones['metodo_pago'] ?? 'efectivo',
                'estado_pago' => $opciones['estado_pago'] ?? 'pendiente',
                'observaciones' => $opciones['observaciones'] ?? null,
            ]);

            $subtotal = 0;
            foreach ($salida->detalles as $detalle) {
                $precio = (float) ($detalle->producto?->precio_distribuidor ?? 0);
                $cantidad = (float) $detalle->cantidad;
                $lineaSubtotal = round($cantidad * $precio, 2);
                $subtotal += $lineaSubtotal;

                $factura->detalles()->create([
                    'producto_id' => $detalle->producto_id,
                    'lote_id' => $detalle->lote_id,
                    'cantidad' => $cantidad,
                    'precio_venta' => $precio,
                    'subtotal' => $lineaSubtotal,
                ]);
            }

            $descuento = (float) ($opciones['descuento'] ?? 0);
            $total = max(0, round($subtotal - $descuento, 2));
            $factura->update([
                'subtotal' => round($subtotal, 2),
                'total_pagar' => $total,
            ]);

            if (($opciones['estado_pago'] ?? 'pendiente') === 'pagado') {
                $factura->pagos()->create([
                    'monto' => $total,
                    'metodo' => $opciones['metodo_pago'] ?? 'efectivo',
                    'estado' => 'pagado',
                    'fecha_pago' => now(),
                    'referencia' => $opciones['referencia_pago'] ?? null,
                    'registrado_por_id' => $usuario->id,
                ]);
            }

            $factura = $factura->fresh()->load($this->relaciones());

            if (!empty($opciones['enviar_correo'])) {
                $this->enviarPorCorreo($factura, $opciones['correo_destino'] ?? null);
            }

            return $factura;
        });
    }

    public function enviarPorCorreo(Factura $factura, ?string $emailDestino = null, bool $forzar = false): bool
    {
        $factura->loadMissing($this->relaciones());

        if (!$forzar && $factura->correo_enviado_at) {
            return true;
        }

        $email = $emailDestino ?: $factura->cliente?->correo;
        if (!$email) {
            return false;
        }

        Mail::to($email)->send(new FacturaVentaMail($factura));

        $factura->update([
            'correo_enviado_at' => now(),
            'correo_destino' => $email,
        ]);

        return true;
    }

    private function resolverCliente(SalidaRevision $salida, ?int $clienteId): Cliente
    {
        if ($clienteId) {
            return Cliente::findOrFail($clienteId);
        }

        if ($salida->destino?->cliente) {
            return $salida->destino->cliente;
        }

        return Cliente::create([
            'destino_id' => $salida->destino_id,
            'razon_social' => $salida->destino?->nombre ?: 'Cliente sin nombre',
            'nit_ci' => '0',
            'nombre_encargado' => null,
            'telefono' => null,
            'correo' => null,
            'direccion' => $salida->destino?->direccion,
            'activo' => true,
        ]);
    }

    private function siguienteNumeroFactura(): int
    {
        return ((int) Factura::max('numero_factura')) + 1;
    }
}
