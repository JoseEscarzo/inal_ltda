<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoteRequest extends FormRequest
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
            'codigo_lote'  => 'nullable|string|max:50|unique:lotes,codigo_lote',
            'producto_id'  => 'required|exists:productos,id_producto',
            'linea_id'     => 'required|exists:lineas_produccion,id_linea',
            'usuario_id'   => 'required|exists:users,id',
            'cantidad'     => 'required|numeric|min:0.001',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'turno'        => 'nullable|string|max:20',
            'estado'       => 'nullable|string|max:20',
            'observaciones' => 'nullable|string',
        ];
    }
}
