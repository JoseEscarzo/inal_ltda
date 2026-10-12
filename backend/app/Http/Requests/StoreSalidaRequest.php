<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * nro_informe ya NO se valida aquí: se genera automáticamente en el Service.
     */
    public function rules(): array
    {
        return [
            'despachado_por_id'      => 'required|exists:users,id',
            'autorizado_por_id'      => 'nullable|exists:users,id',
            'distribuidor_id'        => 'required|exists:users,id',
            'destino_id'             => 'nullable|exists:destinos,id_destino',
            'turno'                  => 'nullable|string|max:20',
            'observaciones'          => 'nullable|string',
            'detalles'               => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id_producto',
            'detalles.*.cantidad'    => 'required|integer|min:1',
            'detalles.*.lote_id'     => 'nullable|exists:lotes,id_lote',
        ];
    }
}
