<?php

namespace App\DTOs;

class LineaProduccionDTO
{
    public function __construct(
        public readonly string  $nombre,
        public readonly ?string $descripcion,
        public readonly ?string $estado,
        public readonly ?string $turno,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre:      $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            estado:      $data['estado']      ?? null,
            turno:       $data['turno']       ?? null,
        );
    }
}
