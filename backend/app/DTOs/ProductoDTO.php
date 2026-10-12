<?php

namespace App\DTOs;

class ProductoDTO
{
    public function __construct(
        public readonly string  $nombre,
        public readonly string  $codigo,
        public readonly ?string $descripcion,
        public readonly ?string $categoria,
        public readonly ?string $tipo_producto,
        public readonly ?string $presentacion,
        public readonly float   $precio_costo = 0,
        public readonly float   $precio_distribuidor = 0,
        public readonly float   $precio_venta_publico = 0,
        public readonly bool    $activo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre:        $data['nombre'],
            codigo:        $data['codigo'],
            descripcion:   $data['descripcion']   ?? null,
            categoria:     $data['categoria']     ?? null,
            tipo_producto: $data['tipo_producto'] ?? null,
            presentacion:  $data['presentacion']  ?? null,
            precio_costo:  (float) ($data['precio_costo'] ?? 0),
            precio_distribuidor: (float) ($data['precio_distribuidor'] ?? 0),
            precio_venta_publico: (float) ($data['precio_venta_publico'] ?? 0),
            activo:        $data['activo']        ?? true,
        );
    }
}
