<?php

namespace App\Http\Controllers;

use App\DTOs\ProveedorDTO;
use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Models\Proveedor;
use App\Services\ProveedorService;
use Illuminate\Http\JsonResponse;

class ProveedorController extends Controller
{
    public function __construct(private ProveedorService $service) {}

    public function index(): JsonResponse { return response()->json($this->service->listar()); }
    public function inactivos(): JsonResponse { return response()->json($this->service->inactivos()); }
    public function show(Proveedor $proveedor): JsonResponse { return response()->json($proveedor); }

    public function store(StoreProveedorRequest $request): JsonResponse
    {
        return response()->json($this->service->crear(ProveedorDTO::fromArray($request->validated())), 201);
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor): JsonResponse
    {
        $data = array_merge($proveedor->toArray(), $request->validated());
        return response()->json($this->service->actualizar($proveedor, ProveedorDTO::fromArray($data)));
    }

    public function archivar(Proveedor $proveedor): JsonResponse { return response()->json($this->service->archivar($proveedor)); }
    public function activar(Proveedor $proveedor): JsonResponse { return response()->json($this->service->activar($proveedor)); }

    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $this->service->eliminar($proveedor);
        return response()->json(['message' => 'Proveedor eliminado definitivamente']);
    }
}
