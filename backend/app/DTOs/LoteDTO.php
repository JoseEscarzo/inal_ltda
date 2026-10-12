<?php

namespace App\DTOs;

class LoteDTO
{
    public function __construct(
        public readonly string  $codigo_lote,
        public readonly ?int    $producto_id,
        public readonly ?int    $linea_id,
        public readonly ?int    $usuario_id,
        public readonly ?float  $cantidad,
        public readonly ?string $fecha_inicio,
        public readonly ?string $fecha_fin,
        public readonly ?string $turno,
        public readonly ?string $estado,
        public readonly ?string $observaciones,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            codigo_lote:  $data['codigo_lote'] ?? '',
            producto_id:  isset($data['producto_id']) ? (int) $data['producto_id'] : null,
            linea_id:     isset($data['linea_id']) ? (int) $data['linea_id'] : null,
            usuario_id:   isset($data['usuario_id']) ? (int) $data['usuario_id'] : null,
            cantidad:     isset($data['cantidad']) ? (float) $data['cantidad'] : null,
            fecha_inicio: $data['fecha_inicio'] ?? null,
            fecha_fin:    $data['fecha_fin']    ?? null,
            turno:        $data['turno']        ?? null,
            estado:       $data['estado']       ?? null,
            observaciones: $data['observaciones'] ?? null,
        );
    }
}
