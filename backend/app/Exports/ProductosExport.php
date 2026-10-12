<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles
{
    public function __construct(
        private readonly ?string $buscar = null,
        private readonly ?string $categoria = null,
    ) {}

    public function collection()
    {
        return Producto::query()
            ->when($this->buscar, function ($query) {
                $buscar = $this->buscar;
                $query->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('codigo', 'ILIKE', "%{$buscar}%")
                        ->orWhere('categoria', 'ILIKE', "%{$buscar}%");
                });
            })
            ->when($this->categoria, fn ($query) => $query->where('categoria', $this->categoria))
            ->orderBy('id_producto', 'asc')
            ->get([
            'id_producto',
            'nombre',
            'codigo',
            'descripcion',
            'categoria',
            'tipo_producto',
            'presentacion'
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Código',
            'Descripción',
            'Categoría',
            'Tipo Producto',
            'Presentación',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezados
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],

            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '2B2353'],
            ],

            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                ],
            ],
        ]);

        // Bordes para toda la tabla
        $ultimaFila = $sheet->getHighestRow();

        $sheet->getStyle("A1:G{$ultimaFila}")
            ->applyFromArray([
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
