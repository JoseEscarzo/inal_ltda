<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistribucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lote_id' => 'required|exists:lotes,id_lote',
            'destino_id' => 'required|exists:destinos,id_destino',
            'responsable_id' => 'required|exists:users,id',
            'fecha_envio' => 'nullable|date',
            'cantidad_productos' => 'required|integer|min:1',
            'estado' => ['nullable', Rule::in(['designado', 'entregado'])],
            'observaciones' => 'nullable|string',
        ];
    }
}
