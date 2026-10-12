<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'nombre' => 'sometimes|required|string|max:150',

            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('productos', 'codigo')
                    ->ignore($producto->id_producto, 'id_producto'),
            ],

            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'tipo_producto' => 'nullable|string|max:100',
            'presentacion' => 'nullable|string|max:100',
            'precio_costo' => 'nullable|numeric|min:0',
            'precio_distribuidor' => 'nullable|numeric|min:0',
            'precio_venta_publico' => 'nullable|numeric|min:0',
            'activo' => 'sometimes|boolean',
            'receta' => 'sometimes|array',
            'receta.*.materia_prima_id' => 'required_with:receta|exists:productos,id_producto',
            'receta.*.cantidad_por_unidad' => 'required_with:receta|numeric|min:0.001',
            'receta.*.cantidad_total' => 'required_with:receta|numeric|min:0.001',
            'receta.*.unidades_producidas' => 'required_with:receta|integer|min:1',
            'receta.*.unidad' => 'nullable|string|max:50',
        ];
    }
}
