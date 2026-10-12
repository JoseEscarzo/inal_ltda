<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoteRequest extends FormRequest
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
        $id = $this->route('lote')?->id_lote ?? $this->route('lote');

        return [
            'codigo_lote'  => "sometimes|nullable|string|max:50|unique:lotes,codigo_lote,{$id},id_lote",
            'producto_id'  => 'sometimes|required|exists:productos,id_producto',
            'linea_id'     => 'sometimes|required|exists:lineas_produccion,id_linea',
            'usuario_id'   => 'sometimes|required|exists:users,id',
            'cantidad'     => 'sometimes|required|numeric|min:0.001',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'turno'        => 'nullable|string|max:20',
            'estado'       => 'nullable|string|max:20',
            'observaciones' => 'nullable|string',
        ];
    }
}
