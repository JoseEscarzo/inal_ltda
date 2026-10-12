<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destino_id' => 'nullable|exists:destinos,id_destino',
            'razon_social' => 'required|string|max:180',
            'nit_ci' => 'nullable|string|max:40',
            'nombre_encargado' => 'nullable|string|max:180',
            'telefono' => 'nullable|string|max:40',
            'correo' => 'nullable|email|max:180',
            'direccion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ];
    }
}
