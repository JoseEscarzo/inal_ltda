<?php

namespace App\Http\Controllers;

use App\DTOs\ProductoDTO;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function __construct(private ProductoService $service) {}

    // GET /productos  → solo activos
    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    // GET /productos/inactivos  → solo inactivos
    public function inactivos(): JsonResponse
    {
        return response()->json($this->service->listarInactivos());
    }

    public function show(Producto $producto): JsonResponse
    {
        return response()->json($producto->load('receta.materiaPrima'));
    }

    public function store(StoreProductoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $dto = ProductoDTO::fromArray($data);
        return response()->json($this->service->crear($dto, $data['receta'] ?? []), 201);
    }

    public function update(UpdateProductoRequest $request, Producto $producto): JsonResponse
    {
        $data = $request->validated();
        $dto = ProductoDTO::fromArray(array_merge(
            $producto->toArray(),
            $data
        ));
        return response()->json($this->service->actualizar($producto, $dto, $data['receta'] ?? null));
    }

    // DELETE /productos/{producto}  → soft-delete (activo = false)
    public function destroy(Producto $producto): JsonResponse
    {
        $this->service->eliminar($producto);
        return response()->json(['message' => 'Producto desactivado']);
    }

    // PATCH /productos/{producto}/activar  → reactiva
    public function activar(Producto $producto): JsonResponse
    {
        return response()->json($this->service->activar($producto));
    }

    // DELETE /productos/{producto}/definitivo  → borra de la BD
    public function eliminarDefinitivo(Producto $producto): JsonResponse
    {
        $this->service->eliminarDefinitivo($producto);
        return response()->json(['message' => 'Producto eliminado permanentemente']);
    }
}
