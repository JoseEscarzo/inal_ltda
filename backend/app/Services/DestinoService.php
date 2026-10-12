<?php
namespace App\Services;

use App\DTOs\DestinoDTO;
use App\Models\Destino;
use Illuminate\Support\Arr;

class DestinoService
{
    public function listar()
    {
        return Destino::with('cliente')->where('activo', true)->orderBy('nombre')->get();
    }

    public function inactivos()
    {
        return Destino::with('cliente')->where('activo', false)->orderBy('nombre')->get();
    }

    public function crear(DestinoDTO $dto, array $cliente = []): Destino
    {
        $destino = Destino::create([
            'nombre'    => $dto->nombre,
            'ciudad'    => $dto->ciudad,
            'tipo'      => $dto->tipo,
            'direccion' => $dto->direccion,
            'activo'    => true,
        ]);

        $this->sincronizarCliente($destino, $cliente);

        return $destino->fresh('cliente');
    }

    public function actualizar(Destino $destino, DestinoDTO $dto, array $cliente = []): Destino
    {
        $destino->update([
            'nombre'    => $dto->nombre,
            'ciudad'    => $dto->ciudad,
            'tipo'      => $dto->tipo,
            'direccion' => $dto->direccion,
        ]);
        $this->sincronizarCliente($destino, $cliente);

        return $destino->fresh('cliente');
    }

    public function archivar(Destino $destino): Destino
    {
        $destino->update(['activo' => false]);
        return $destino->fresh();
    }

    public function activar(Destino $destino): Destino
    {
        $destino->update(['activo' => true]);
        return $destino->fresh();
    }

    public function eliminar(Destino $destino): void
    {
        $destino->delete();
    }

    private function sincronizarCliente(Destino $destino, array $cliente): void
    {
        $cliente = Arr::only($cliente, ['razon_social', 'nit_ci', 'nombre_encargado', 'telefono', 'correo', 'direccion']);
        $tieneDatos = collect($cliente)->filter(fn ($valor) => $valor !== null && $valor !== '')->isNotEmpty();

        if (!$tieneDatos) {
            return;
        }

        $destino->cliente()->updateOrCreate(
            ['destino_id' => $destino->id_destino],
            [
                'razon_social' => $cliente['razon_social'] ?? $destino->nombre,
                'nit_ci' => $cliente['nit_ci'] ?? null,
                'nombre_encargado' => $cliente['nombre_encargado'] ?? null,
                'telefono' => $cliente['telefono'] ?? null,
                'correo' => $cliente['correo'] ?? null,
                'direccion' => $cliente['direccion'] ?? $destino->direccion,
                'activo' => true,
            ]
        );
    }
}
