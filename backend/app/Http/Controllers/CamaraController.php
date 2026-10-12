<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCamaraRequest;
use App\Http\Requests\UpdateCamaraRequest;
use App\Models\Camara;
use App\Services\CamaraService;
use Illuminate\Http\JsonResponse;

class CamaraController extends Controller
{
    public function __construct(private CamaraService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function store(StoreCamaraRequest $request): JsonResponse
    {
        return response()->json($this->service->crear($request->validated()), 201);
    }

    public function show(Camara $camara): JsonResponse
    {
        return response()->json($camara->load('linea'));
    }

    public function update(UpdateCamaraRequest $request, Camara $camara): JsonResponse
    {
        return response()->json($this->service->actualizar($camara, $request->validated()));
    }

    public function destroy(Camara $camara): JsonResponse
    {
        $this->service->eliminar($camara);
        return response()->json(['message' => 'Camara eliminada.']);
    }
}
