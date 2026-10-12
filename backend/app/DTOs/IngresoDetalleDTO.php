<?php

namespace App\DTOs;

class IngresoDetalleDTO
{
    public function __construct(
        public readonly int  $producto_id,
        public readonly int  $cantidad,
        public readonly ?int $lote_id,
        public readonly int  $cantidad_aprobada = 0,
        public readonly int  $cantidad_rechazada = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            producto_id:        $data['producto_id'],
            cantidad:           $data['cantidad'],
            lote_id:            $data['lote_id'] ?? null,
            cantidad_aprobada:  $data['cantidad_aprobada'] ?? 0,
            // 👇 ESTE ERA EL ERROR (decía $dara)
            cantidad_rechazada: $data['cantidad_rechazada'] ?? 0,
        );
    }
}
