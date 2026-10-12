<?php

namespace App\Exports;

use App\Models\Lote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LotesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly ?string $buscar = null,
        private readonly ?string $estado = null,
        private readonly ?string $productoId = null,
    ) {}

    public function collection()
    {
        return Lote::query()
            ->with(['producto', 'linea', 'usuario'])
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->productoId, fn ($q) => $q->where('producto_id', $this->productoId))
            ->when($this->buscar, function ($q) {
                $buscar = $this->buscar;
                $q->where('codigo_lote', 'ILIKE', "%{$buscar}%")
                    ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('linea', fn ($l) => $l->where('nombre', 'ILIKE', "%{$buscar}%"));
            })
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn ($lote) => [
                $lote->codigo_lote,
                $lote->producto?->nombre,
                $lote->cantidad,
                optional($lote->fecha_inicio)->format('Y-m-d'),
                optional($lote->fecha_fin)->format('Y-m-d'),
                $lote->linea?->nombre,
                $lote->turno,
                $lote->estado,
                $lote->usuario?->name,
            ]);
    }

    public function headings(): array
    {
        return ['Lote', 'Producto', 'Cantidad', 'Fecha produccion', 'Vencimiento', 'Linea', 'Turno', 'Estado', 'Responsable'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2B2353']],
        ]);

        return [];
    }
}
