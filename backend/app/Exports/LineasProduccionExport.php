<?php

namespace App\Exports;

use App\Models\LineaProduccion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LineasProduccionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly ?string $buscar = null,
        private readonly ?string $estado = null,
    ) {}

    public function collection()
    {
        return LineaProduccion::query()
            ->withCount('lotes')
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->buscar, function ($q) {
                $buscar = $this->buscar;
                $q->where('nombre', 'ILIKE', "%{$buscar}%")
                    ->orWhere('turno', 'ILIKE', "%{$buscar}%")
                    ->orWhere('descripcion', 'ILIKE', "%{$buscar}%");
            })
            ->orderBy('nombre')
            ->get()
            ->map(fn ($linea) => [
                $linea->id_linea,
                $linea->nombre,
                $linea->turno,
                $linea->estado,
                $linea->lotes_count,
                $linea->descripcion,
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Linea', 'Turno', 'Estado', 'Lotes', 'Descripcion'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2B2353']],
        ]);

        return [];
    }
}
