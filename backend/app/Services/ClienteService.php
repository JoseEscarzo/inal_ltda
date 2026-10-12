<?php

namespace App\Services;

use App\Models\Cliente;

class ClienteService
{
    public function listar()
    {
        return Cliente::with('destino')->where('activo', true)->orderBy('razon_social')->get();
    }

    public function crear(array $data): Cliente
    {
        return Cliente::create($data)->load('destino');
    }

    public function actualizar(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);
        return $cliente->fresh('destino');
    }

    public function archivar(Cliente $cliente): void
    {
        $cliente->update(['activo' => false]);
    }
}
