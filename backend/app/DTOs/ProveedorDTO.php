<?php

namespace App\DTOs;

class ProveedorDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $nit,
        public readonly ?string $contacto,
        public readonly ?string $telefono,
        public readonly ?string $correo,
        public readonly ?string $direccion,
        public readonly bool $activo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'],
            nit: $data['nit'] ?? null,
            contacto: $data['contacto'] ?? null,
            telefono: $data['telefono'] ?? null,
            correo: $data['correo'] ?? null,
            direccion: $data['direccion'] ?? null,
            activo: $data['activo'] ?? true,
        );
    }
}
