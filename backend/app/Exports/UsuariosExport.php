<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsuariosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly ?string $buscar = null,
        private readonly bool $activo = true,
    ) {}

    public function collection()
    {
        return User::query()
            ->with('rol')
            ->where('activo', $this->activo)
            ->when($this->buscar, function ($query) {
                $buscar = $this->buscar;
                $query->where(function ($sub) use ($buscar) {
                    $sub->where('name', 'ILIKE', "%{$buscar}%")
                        ->orWhere('email', 'ILIKE', "%{$buscar}%")
                        ->orWhere('nro_carnet', 'ILIKE', "%{$buscar}%");
                });
            })
            ->orderBy('id')
            ->get();
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone,
            $user->nro_carnet,
            optional($user->fecha_nacimiento)->format('Y-m-d'),
            $user->rol?->nombre,
            $user->activo ? 'Activo' : 'Inactivo',
        ];
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'Correo', 'Celular', 'Nro Carnet', 'Fecha nacimiento', 'Rol', 'Estado'];
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
