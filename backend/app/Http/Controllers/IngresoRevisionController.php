<?php

namespace App\Http\Controllers;

use App\DTOs\IngresoRevisionDTO;
use App\Http\Requests\StoreIngresoRequest;
use App\Models\IngresoRevision;
use App\Services\IngresoRevisionService;
use Illuminate\Http\JsonResponse;

class IngresoRevisionController extends Controller
{
    public function __construct(private IngresoRevisionService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function show(IngresoRevision $ingresoRevision): JsonResponse
    {
        return response()->json(
            $ingresoRevision->load([
                'detalles.producto',
                'entregadoPor',
                'proveedor',
                'recibidoPor',
            ])
        );
    }

    public function store(StoreIngresoRequest $request): JsonResponse
    {
        $dto = IngresoRevisionDTO::fromArray($request->validated());
        return response()->json($this->service->crear($dto), 201);
    }

    public function destroy(IngresoRevision $ingresoRevision): JsonResponse
    {
        $this->service->eliminar($ingresoRevision);
        return response()->json(['message' => 'Ingreso eliminado'], 200);
    }
}
