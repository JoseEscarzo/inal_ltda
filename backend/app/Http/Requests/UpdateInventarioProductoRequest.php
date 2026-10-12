<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInventarioProductoRequest extends FormRequest
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
            'producto_id'        => 'sometimes|required|exists:productos,id_producto',
            'cantidad'           => 'sometimes|required|integer|min:0',
            'stock'              => 'sometimes|required|integer|min:0',
            'lote_id'            => 'nullable|exists:lotes,id_lote',
            'deteccion_id'       => 'nullable|exists:detecciones,id_deteccion',
            'ingreso_detalle_id' => 'nullable|exists:ingreso_detalles,id_detalle_ingreso',
            'clasificacion'      => 'nullable|string|max:20',
            'ruta_evidencia'     => 'nullable|string|max:300',
            'activo'             => 'sometimes|boolean',
        ];
    }
}
