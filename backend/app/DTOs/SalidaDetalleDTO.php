<?php

namespace App\DTOs;

class SalidaDetalleDTO
{
    public function __construct(
        public readonly int  $producto_id,
        public readonly int  $cantidad,
        public readonly ?int $lote_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            producto_id: $data['producto_id'],
            cantidad:    $data['cantidad'],
            lote_id:     $data['lote_id'] ?? null,
        );
    }
}
