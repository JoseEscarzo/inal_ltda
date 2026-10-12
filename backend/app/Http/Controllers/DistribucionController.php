<?php

namespace App\Http\Controllers;

use App\DTOs\DistribucionDTO;
use App\Http\Requests\StoreDistribucionRequest;
use App\Http\Requests\UpdateDistribucionRequest;
use App\Models\Distribucion;
use App\Services\DistribucionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistribucionController extends Controller
{
    public function __construct(private DistribucionService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->listar($request->user(), $request->query(), 100));
    }

    public function show(Distribucion $distribucion): JsonResponse
    {
        return response()->json($distribucion->load(['lote.producto.receta.materiaPrima', 'lote.linea', 'destino', 'responsable']));
    }

    public function store(StoreDistribucionRequest $request): JsonResponse
    {
        $dto = DistribucionDTO::fromArray($request->validated());
        return response()->json($this->service->crear($dto), 201);
    }

    public function update(UpdateDistribucionRequest $request, Distribucion $distribucion): JsonResponse
    {
        $dto = DistribucionDTO::fromArray(array_merge($distribucion->toArray(), $request->validated()));
        return response()->json($this->service->actualizar($distribucion, $dto, $request->user()));
    }

    public function destroy(Distribucion $distribucion): JsonResponse
    {
        $this->service->eliminar($distribucion);
        return response()->json(['message' => 'Distribucion eliminada']);
    }
}
