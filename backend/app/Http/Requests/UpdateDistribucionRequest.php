<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistribucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lote_id' => 'sometimes|required|exists:lotes,id_lote',
            'destino_id' => 'sometimes|required|exists:destinos,id_destino',
            'responsable_id' => 'sometimes|required|exists:users,id',
            'fecha_envio' => 'nullable|date',
            'cantidad_productos' => 'sometimes|required|integer|min:1',
            'estado' => ['sometimes', 'required', Rule::in(['designado', 'entregado'])],
            'observaciones' => 'nullable|string',
        ];
    }
}
