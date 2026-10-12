<?php

namespace App\DTOs;

class InventarioProductoDTO
{
    public function __construct(
        public readonly int $producto_id,
        public readonly int $cantidad,
        public readonly int $stock,
        public readonly ?int $lote_id = null,
        public readonly ?int $deteccion_id = null,
        public readonly ?int $ingreso_detalle_id = null,
        public readonly ?string $clasificacion = null,
        public readonly ?string $ruta_evidencia = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            producto_id:        $data['producto_id'],
            cantidad:           $data['cantidad'],
            stock:              $data['stock'],
            lote_id:            $data['lote_id'] ?? null,
            deteccion_id:       $data['deteccion_id'] ?? null,
            ingreso_detalle_id: $data['ingreso_detalle_id'] ?? null,
            clasificacion:      $data['clasificacion'] ?? null,
            ruta_evidencia:     $data['ruta_evidencia'] ?? null,
            activo:             $data['activo'] ?? true,
        );
    }
}
