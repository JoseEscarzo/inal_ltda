<?php
namespace App\Http\Controllers;

use App\DTOs\DestinoDTO;
use App\Http\Requests\StoreDestinoRequest;
use App\Http\Requests\UpdateDestinoRequest;
use App\Models\Destino;
use App\Services\DestinoService;
use Illuminate\Http\JsonResponse;

class DestinoController extends Controller
{
    public function __construct(private DestinoService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function inactivos(): JsonResponse
    {
        return response()->json($this->service->inactivos());
    }

    public function show(Destino $destino): JsonResponse
    {
        return response()->json($destino->load('cliente'));
    }

    public function store(StoreDestinoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $dto = DestinoDTO::fromArray($data);
        return response()->json($this->service->crear($dto, $data['cliente'] ?? []), 201);
    }

    public function update(UpdateDestinoRequest $request, Destino $destino): JsonResponse
    {
        $data = $request->validated();
        $dto = DestinoDTO::fromArray(array_merge($destino->toArray(), $data));
        return response()->json($this->service->actualizar($destino, $dto, $data['cliente'] ?? []));
    }

    public function archivar(Destino $destino): JsonResponse
    {
        return response()->json($this->service->archivar($destino));
    }

    public function activar(Destino $destino): JsonResponse
    {
        return response()->json($this->service->activar($destino));
    }

    public function destroy(Destino $destino): JsonResponse
    {
        $this->service->eliminar($destino);
        return response()->json(['message' => 'Destino eliminado definitivamente'], 200);
    }
}
