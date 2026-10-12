<?php
namespace App\DTOs;

class DestinoDTO
{
    public function __construct(
        public readonly string  $nombre,
        public readonly ?string $ciudad,
        public readonly ?string $tipo,
        public readonly ?string $direccion,
        public readonly bool    $activo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre:    $data['nombre'],
            ciudad:    $data['ciudad']    ?? null,
            tipo:      $data['tipo']      ?? null,
            direccion: $data['direccion'] ?? null,
            activo:    $data['activo']    ?? true,
        );
    }
}
