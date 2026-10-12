<?php

namespace App\Exports;

use App\Models\InventarioProducto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventarioProductosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly ?string $buscar = null,
        private readonly ?string $desde = null,
        private readonly ?string $hasta = null,
        private readonly ?string $tipoProducto = null,
        private readonly ?string $clasificacion = null,
    ) {}

    public function collection()
    {
        return InventarioProducto::query()
            ->with('producto')
            ->where('activo', true)
            ->when($this->desde, fn ($q) => $q->whereDate('fecha_registro', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha_registro', '<=', $this->hasta))
            ->when($this->tipoProducto, fn ($q) => $q->whereHas('producto', fn ($p) => $p->where('tipo_producto', $this->tipoProducto)))
            ->when($this->clasificacion, fn ($q) => $q->where('clasificacion', $this->clasificacion))
            ->when($this->buscar, function ($q) {
                $buscar = $this->buscar;
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('clasificacion', 'ILIKE', "%{$buscar}%")
                        ->orWhereHas('producto', fn ($p) => $p
                            ->where('nombre', 'ILIKE', "%{$buscar}%")
                            ->orWhere('codigo', 'ILIKE', "%{$buscar}%")
                            ->orWhere('tipo_producto', 'ILIKE', "%{$buscar}%"));
                });
            })
            ->orderByDesc('fecha_registro')
            ->get();
    }

    public function map($row): array
    {
        return [
            $row->id_inventario,
            optional($row->fecha_registro)->format('Y-m-d H:i'),
            $row->producto?->nombre,
            $row->producto?->codigo,
            $row->producto?->tipo_producto,
            $row->clasificacion,
            $row->cantidad,
            $row->stock,
        ];
    }

    public function headings(): array
    {
        return ['ID', 'Fecha', 'Producto', 'Codigo', 'Tipo', 'Movimiento', 'Cantidad', 'Stock'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2B2353']],
        ]);

        return [];
    }
}
