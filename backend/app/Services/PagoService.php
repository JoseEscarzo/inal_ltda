<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Pago;
use App\Models\User;

class PagoService
{
    public function registrar(Factura $factura, array $data, ?User $usuario): Pago
    {
        $pago = $factura->pagos()->create([
            'monto' => $data['monto'] ?? $factura->total_pagar,
            'metodo' => $data['metodo'] ?? 'efectivo',
            'estado' => $data['estado'] ?? 'pagado',
            'fecha_pago' => $data['fecha_pago'] ?? now(),
            'referencia' => $data['referencia'] ?? null,
            'registrado_por_id' => $usuario?->id,
        ]);

        if ($pago->estado === 'pagado') {
            $factura->update([
                'estado_pago' => 'pagado',
                'metodo_pago' => $pago->metodo,
            ]);
        }

        return $pago->load('factura');
    }
}
