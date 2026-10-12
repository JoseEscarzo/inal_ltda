<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    public function __construct(private ClienteService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        return response()->json($this->service->crear($request->validated()), 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente->load('destino'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        return response()->json($this->service->actualizar($cliente, $request->validated()));
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->service->archivar($cliente);
        return response()->json(['message' => 'Cliente archivado.']);
    }

}
