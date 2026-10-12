<?php

namespace App\Http\Controllers;

use App\DTOs\LoteDTO;
use App\Http\Requests\StoreLoteRequest;
use App\Http\Requests\UpdateLoteRequest;
use App\Models\Lote;
use App\Services\LoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    public function __construct(private LoteService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->listar((int) $request->query('per_page', 15)));
    }

    public function show(Lote $lote): JsonResponse
    {
        return response()->json($lote->load(['producto.receta.materiaPrima', 'linea', 'usuario', 'distribuciones.destino']));
    }

    public function store(StoreLoteRequest $request): JsonResponse
    {
        $dto = LoteDTO::fromArray($request->validated());
        return response()->json($this->service->crear($dto), 201);
    }

    public function update(UpdateLoteRequest $request, Lote $lote): JsonResponse
    {
        $dto = LoteDTO::fromArray(array_merge(
            $lote->toArray(),
            $request->validated()
        ));
        return response()->json($this->service->actualizar($lote, $dto));
    }

    public function destroy(Lote $lote): JsonResponse
    {
        $this->service->eliminar($lote);
        return response()->json(['message' => 'Lote desactivado'], 200);
    }

    public function activar(Lote $lote): JsonResponse
    {
        return response()->json($this->service->activar($lote));
    }
}
