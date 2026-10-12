<?php

namespace App\DTOs;

class DistribucionDTO
{
    public function __construct(
        public readonly ?int $lote_id,
        public readonly ?int $destino_id,
        public readonly ?int $responsable_id,
        public readonly ?string $fecha_envio,
        public readonly ?int $cantidad_productos,
        public readonly ?string $estado,
        public readonly ?string $observaciones,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            lote_id: $data['lote_id'] ?? null,
            destino_id: $data['destino_id'] ?? null,
            responsable_id: $data['responsable_id'] ?? null,
            fecha_envio: $data['fecha_envio'] ?? null,
            cantidad_productos: isset($data['cantidad_productos']) ? (int) $data['cantidad_productos'] : null,
            estado: $data['estado'] ?? null,
            observaciones: $data['observaciones'] ?? null,
        );
    }
}
