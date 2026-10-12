<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProveedorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|required|string|max:150',
            'nit' => 'nullable|string|max:40',
            'contacto' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:40',
            'correo' => 'nullable|email|max:180',
            'direccion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ];
    }
}
