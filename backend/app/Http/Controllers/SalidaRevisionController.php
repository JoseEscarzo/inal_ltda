<?php

namespace App\Http\Controllers;

use App\DTOs\SalidaRevisionDTO;
use App\Http\Requests\StoreSalidaRequest;
use App\Models\SalidaRevision;
use App\Services\SalidaRevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalidaRevisionController extends Controller
{
    public function __construct(private SalidaRevisionService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function show(SalidaRevision $salidaRevision): JsonResponse
    {
        return response()->json(
            $salidaRevision->load([
                'detalles.producto',
                'detalles.lote',
                'despachadoPor',
                'autorizadoPor',
                'distribuidor',
                'destino',
                'factura.cliente',
            ])
        );
    }

    public function store(StoreSalidaRequest $request): JsonResponse
    {
        $dto = SalidaRevisionDTO::fromArray($request->validated());
        return response()->json($this->service->crear($dto, $request->user()), 201);
    }

    public function aprobar(SalidaRevision $salidaRevision, Request $request): JsonResponse
    {
        return response()->json($this->service->aprobar($salidaRevision, $request->user()));
    }

    public function rechazar(SalidaRevision $salidaRevision, Request $request): JsonResponse
    {
        $this->service->rechazar($salidaRevision, $request->user());
        return response()->json(['message' => 'Salida rechazada']);
    }

    public function destroy(SalidaRevision $salidaRevision): JsonResponse
    {
        $this->service->eliminar($salidaRevision);
        return response()->json(['message' => 'Salida eliminada'], 200);
    }
}
