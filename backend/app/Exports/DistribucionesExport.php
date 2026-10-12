<?php

namespace App\Exports;

use App\Models\IngresoRevision;
use App\Models\SalidaRevision;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DistribucionesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly ?string $tipo = null,
        private readonly ?string $buscar = null,
    ) {}

    public function collection()
    {
        $rows = collect();

        if ($this->tipo !== 'salidas') {
            $rows = $rows->merge($this->ingresos());
        }

        if ($this->tipo !== 'ingresos') {
            $rows = $rows->merge($this->salidas());
        }

        return $rows->sortByDesc('fecha')->values()->map(fn ($row) => $this->formatRow($row));
    }

    private function ingresos(): Collection
    {
        return IngresoRevision::query()
            ->with(['detalles.producto', 'entregadoPor', 'recibidoPor'])
            ->when($this->buscar, function ($query) {
                $buscar = $this->buscar;
                $query->whereHas('entregadoPor', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('recibidoPor', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('detalles.producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"));
            })
            ->get()
            ->map(fn ($ingreso) => [
                'tipo' => 'Ingreso',
                'fecha' => optional($ingreso->fecha_hora)->format('Y-m-d H:i'),
                'codigo' => '#'.str_pad($ingreso->id_ingreso, 3, '0', STR_PAD_LEFT),
                'responsable' => $ingreso->entregadoPor?->name,
                'recibido_por' => $ingreso->recibidoPor?->name,
                'destino' => '',
                'autorizado_por' => '',
                'distribuidor' => '',
                'entradas' => $ingreso->detalles->sum('cantidad_aprobada'),
                'salidas' => 0,
                'total' => $ingreso->detalles->sum('cantidad'),
            ]);
    }

    private function salidas(): Collection
    {
        return SalidaRevision::query()
            ->with(['detalles.producto', 'despachadoPor', 'autorizadoPor', 'distribuidor', 'destino'])
            ->when($this->buscar, function ($query) {
                $buscar = $this->buscar;
                $query->where('nro_informe', 'ILIKE', "%{$buscar}%")
                    ->orWhereHas('destino', fn ($d) => $d->where('nombre', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('despachadoPor', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('distribuidor', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('detalles.producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"));
            })
            ->get()
            ->map(fn ($salida) => [
                'tipo' => 'Salida',
                'fecha' => optional($salida->fecha_hora)->format('Y-m-d H:i'),
                'codigo' => $salida->nro_informe ?: '#'.str_pad($salida->id_salida, 3, '0', STR_PAD_LEFT),
                'responsable' => $salida->despachadoPor?->name,
                'recibido_por' => '',
                'destino' => $salida->destino?->nombre,
                'autorizado_por' => $salida->autorizadoPor?->name,
                'distribuidor' => $salida->distribuidor?->name,
                'entradas' => 0,
                'salidas' => $salida->detalles->sum('cantidad'),
                'total' => $salida->detalles->sum('cantidad'),
            ]);
    }

    public function headings(): array
    {
        return array_values($this->columns());
    }

    public function columns(): array
    {
        return match ($this->tipo) {
            'ingresos' => [
                'tipo' => 'Tipo',
                'fecha' => 'Fecha',
                'codigo' => 'Codigo',
                'responsable' => 'Entregado por',
                'recibido_por' => 'Recibido por',
                'entradas' => 'Entro',
                'total' => 'Total',
            ],
            'salidas' => [
                'tipo' => 'Tipo',
                'fecha' => 'Fecha',
                'codigo' => 'Codigo',
                'responsable' => 'Despachado por',
                'destino' => 'Destino',
                'autorizado_por' => 'Autorizado por',
                'distribuidor' => 'Distribuidor asignado',
                'salidas' => 'Salio',
                'total' => 'Total',
            ],
            default => [
                'tipo' => 'Tipo',
                'fecha' => 'Fecha',
                'codigo' => 'Codigo',
                'responsable' => 'Responsable',
                'recibido_por' => 'Recibido por',
                'destino' => 'Destino',
                'autorizado_por' => 'Autorizado por',
                'distribuidor' => 'Distribuidor asignado',
                'entradas' => 'Entro',
                'salidas' => 'Salio',
                'total' => 'Total',
            ],
        };
    }

    private function formatRow(array $row): array
    {
        return collect(array_keys($this->columns()))
            ->mapWithKeys(fn ($key) => [$key => $row[$key] ?? ''])
            ->all();
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2B2353']],
        ]);

        return [];
    }
}
