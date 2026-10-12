<?php

namespace App\DTOs;

class FacturaDesdeSalidaDTO
{
    public function __construct(
        public readonly ?int $cliente_id = null,
        public readonly string $metodo_pago = 'efectivo',
        public readonly string $estado_pago = 'pendiente',
        public readonly float $descuento = 0,
        public readonly ?string $observaciones = null,
        public readonly bool $enviar_correo = false,
        public readonly ?string $correo_destino = null,
        public readonly ?string $referencia_pago = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cliente_id: isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
            metodo_pago: $data['metodo_pago'] ?? 'efectivo',
            estado_pago: $data['estado_pago'] ?? 'pendiente',
            descuento: (float) ($data['descuento'] ?? 0),
            observaciones: $data['observaciones'] ?? null,
            enviar_correo: (bool) ($data['enviar_correo'] ?? false),
            correo_destino: $data['correo_destino'] ?? null,
            referencia_pago: $data['referencia_pago'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'metodo_pago' => $this->metodo_pago,
            'estado_pago' => $this->estado_pago,
            'descuento' => $this->descuento,
            'observaciones' => $this->observaciones,
            'enviar_correo' => $this->enviar_correo,
            'correo_destino' => $this->correo_destino,
            'referencia_pago' => $this->referencia_pago,
        ];
    }
}
