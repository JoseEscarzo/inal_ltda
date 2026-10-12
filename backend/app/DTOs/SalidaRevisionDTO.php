<?php

namespace App\DTOs;

class SalidaRevisionDTO
{
    public function __construct(
        public readonly int     $despachado_por_id,
        public readonly ?int    $autorizado_por_id,
        public readonly int     $distribuidor_id,
        public readonly ?int    $destino_id,
        public readonly ?string $turno,
        public readonly ?string $observaciones,
        public readonly array   $detalles,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            despachado_por_id: $data['despachado_por_id'],
            autorizado_por_id: $data['autorizado_por_id'] ?? null,
            distribuidor_id:   $data['distribuidor_id'],
            destino_id:        $data['destino_id']    ?? null,
            turno:             $data['turno']         ?? null,
            observaciones:     $data['observaciones'] ?? null,
            detalles:          array_map(
                fn($d) => SalidaDetalleDTO::fromArray($d),
                $data['detalles']
            ),
        );
    }
}
