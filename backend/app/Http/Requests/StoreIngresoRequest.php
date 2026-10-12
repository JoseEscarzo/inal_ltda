<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIngresoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entregado_por_id'              => 'nullable|exists:users,id',
            'proveedor_id'                  => 'required|exists:proveedores,id_proveedor',
            'recibido_por_id'               => 'required|exists:users,id',
            'turno'                         => 'nullable|string|max:20',
            'observaciones'                 => 'nullable|string',
            'detalles'                      => 'required|array|min:1',
            'detalles.*.producto_id'        => 'required|exists:productos,id_producto',
            'detalles.*.cantidad'           => 'required|integer|min:1',
            'detalles.*.lote_id'            => 'nullable|exists:lotes,id_lote',
            'detalles.*.cantidad_aprobada'  =>'nullable|integer|min:0',
            'detalles.*.cantidad_rechazada' =>'nullable|integer|min:0',
        ];
    }
}
