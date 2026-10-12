<?php

namespace App\Exports;

use App\Models\Distribucion;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrazabilidadExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly User $user,
        private readonly ?string $buscar = null,
        private readonly ?string $estado = null,
    ) {}

    public function collection()
    {
        return Distribucion::query()
            ->with(['lote.producto', 'destino', 'responsable'])
            ->when((int) $this->user->id_rol === 5, fn ($q) => $q->where('responsable_id', $this->user->id))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->buscar, function ($q) {
                $buscar = $this->buscar;
                $q->where('estado', 'ILIKE', "%{$buscar}%")
                    ->orWhereHas('lote', fn ($l) => $l->where('codigo_lote', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('lote.producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('destino', fn ($d) => $d->where('nombre', 'ILIKE', "%{$buscar}%"))
                    ->orWhereHas('responsable', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"));
            })
            ->orderByDesc('fecha_envio')
            ->get()
            ->map(fn ($d) => [
                $d->id_distribucion,
                $d->lote?->codigo_lote,
                $d->lote?->producto?->nombre,
                $d->destino?->nombre,
                $d->responsable?->name,
                $d->cantidad_productos,
                $d->estado,
                optional($d->fecha_envio)->format('Y-m-d H:i'),
                $d->observaciones,
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Lote', 'Producto', 'Destino', 'Responsable', 'Cantidad', 'Estado', 'Fecha envio', 'Observaciones'];
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
