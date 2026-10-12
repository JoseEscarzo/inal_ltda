<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacturaFromSalidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'nullable|exists:clientes,id_cliente',
            'metodo_pago' => 'nullable|string|max:40',
            'estado_pago' => 'nullable|in:pendiente,pagado,anulado',
            'descuento' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'enviar_correo' => 'nullable|boolean',
            'correo_destino' => 'nullable|email|max:180',
            'referencia_pago' => 'nullable|string|max:120',
        ];
    }
}
