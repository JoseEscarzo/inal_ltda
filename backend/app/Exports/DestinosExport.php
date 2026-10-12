<?php

namespace App\Exports;

use App\Models\Destino;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DestinosExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $buscar;

    public function __construct($buscar = null)
    {
        $this->buscar = $buscar;
    }

    public function collection()
    {
        return Destino::query()
            ->when($this->buscar, function ($query) {
                $q = $this->buscar;
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'ILIKE', "%{$q}%")
                        ->orWhere('ciudad', 'ILIKE', "%{$q}%")
                        ->orWhere('tipo', 'ILIKE', "%{$q}%")
                        ->orWhere('direccion', 'ILIKE', "%{$q}%");
                });
            })
            ->orderBy('id_destino', 'asc')
            ->get([
                'id_destino',
                'nombre',
                'ciudad',
                'tipo',
                'direccion',
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'Ciudad', 'Tipo', 'Dirección'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '2B2353'],
            ],
        ]);

        $ultimaFila = $sheet->getHighestRow();

        $sheet->getStyle("A1:E{$ultimaFila}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        return [];
    }
}
