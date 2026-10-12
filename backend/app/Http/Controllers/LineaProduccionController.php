<?php

namespace App\Http\Controllers;

use App\DTOs\LineaProduccionDTO;
use App\Http\Requests\StoreLineaProduccionRequest;
use App\Http\Requests\UpdateLineaProduccionRequest;
use App\Models\LineaProduccion;
use App\Services\LineaProduccionService;
use Illuminate\Http\JsonResponse;

class LineaProduccionController extends Controller
{
    public function __construct(private LineaProduccionService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function show(LineaProduccion $lineaProduccion): JsonResponse
    {
        return response()->json($lineaProduccion->load('lotes.producto'));
    }

    public function store(StoreLineaProduccionRequest $request): JsonResponse
    {
        $dto = LineaProduccionDTO::fromArray($request->validated());
        return response()->json($this->service->crear($dto), 201);
    }

    public function update(UpdateLineaProduccionRequest $request, LineaProduccion $lineaProduccion): JsonResponse
    {
        $dto = LineaProduccionDTO::fromArray(array_merge(
            $lineaProduccion->toArray(),
            $request->validated()
        ));
        return response()->json($this->service->actualizar($lineaProduccion, $dto));
    }

    public function destroy(LineaProduccion $lineaProduccion): JsonResponse
    {
        $this->service->eliminar($lineaProduccion);
        return response()->json(['message' => 'Línea de producción eliminada'], 200);
    }
}
