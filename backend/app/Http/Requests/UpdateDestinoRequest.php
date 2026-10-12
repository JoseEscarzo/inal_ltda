<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDestinoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'    => 'sometimes|required|string|max:150',
            'ciudad'    => 'nullable|string|max:100',
            'tipo'      => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'activo'    => 'sometimes|boolean',
            'cliente' => 'nullable|array',
            'cliente.razon_social' => 'nullable|string|max:180',
            'cliente.nit_ci' => 'nullable|string|max:40',
            'cliente.nombre_encargado' => 'nullable|string|max:180',
            'cliente.telefono' => 'nullable|string|max:40',
            'cliente.correo' => 'nullable|email|max:180',
            'cliente.direccion' => 'nullable|string',
        ];
    }
}
