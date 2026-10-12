<?php

namespace App\DTOs;

class IngresoRevisionDTO
{
    public function __construct(
        public readonly ?int    $entregado_por_id,
        public readonly ?int    $proveedor_id,
        public readonly int     $recibido_por_id,
        public readonly ?string $turno,
        public readonly ?string $observaciones,
        public readonly array   $detalles,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            entregado_por_id: $data['entregado_por_id'] ?? null,
            proveedor_id:     $data['proveedor_id'] ?? null,
            recibido_por_id:  $data['recibido_por_id'],
            turno:            $data['turno']         ?? null,
            observaciones:    $data['observaciones'] ?? null,
            detalles:         array_map(
                fn($d) => IngresoDetalleDTO::fromArray($d),
                $data['detalles']
            ),
        );
    }
}
