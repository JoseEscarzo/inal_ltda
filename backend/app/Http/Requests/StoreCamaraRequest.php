<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCamaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'linea_id' => 'nullable|exists:lineas_produccion,id_linea',
            'url_stream' => 'nullable|string|max:300',
            'estado' => 'nullable|string|max:20',
            'ubicacion' => 'nullable|string|max:200',
        ];
    }
}
