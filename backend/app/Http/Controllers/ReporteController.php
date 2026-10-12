<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Destino;
use App\Exports\DestinosExport;
use App\Exports\InventarioProductosExport;
use App\Exports\DistribucionesExport;
use App\Exports\LineasProduccionExport;
use App\Exports\LotesExport;
use App\Exports\TrazabilidadExport;
use App\Exports\UsuariosExport;
use App\Models\LineaProduccion;
use App\Models\Lote;
use App\Models\Distribucion;
use App\Models\IngresoRevision;
use App\Models\InventarioProducto;
use App\Models\SalidaRevision;
use App\Models\User;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function productosPDF(Request $request)
{
    $buscar = $request->query('buscar');
    $categoria = $request->query('categoria');

    $productos = Producto::query()
        ->when($buscar, function ($query) use ($buscar) {
            $query->where(function ($sub) use ($buscar) {
                $sub->where('nombre', 'ILIKE', "%{$buscar}%")
                    ->orWhere('codigo', 'ILIKE', "%{$buscar}%")
                    ->orWhere('categoria', 'ILIKE', "%{$buscar}%");
            });
        })
        ->when($categoria, fn ($query) => $query->where('categoria', $categoria))
        ->orderBy('id_producto', 'asc')
        ->get();

    $pdf = Pdf::loadView('reportes.productos_pdf', compact('productos'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('reporte_productos.pdf');
}

    public function productosExcel(Request $request)
    {
        return Excel::download(
            new ProductosExport($request->query('buscar'), $request->query('categoria')),
            'reporte_productos.xlsx'
        );
    }
    public function destinosPDF(Request $request)
{
    $buscar = $request->query('buscar');

    $destinos = Destino::query()
        ->when($buscar, function ($query) use ($buscar) {
            $query->where(function ($sub) use ($buscar) {
                $sub->where('nombre', 'ILIKE', "%{$buscar}%")
                    ->orWhere('ciudad', 'ILIKE', "%{$buscar}%")
                    ->orWhere('tipo', 'ILIKE', "%{$buscar}%")
                    ->orWhere('direccion', 'ILIKE', "%{$buscar}%");
            });
        })
        ->orderBy('id_destino', 'asc')
        ->get();

    $pdf = Pdf::loadView('reportes.destinos_pdf', compact('destinos'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('reporte_destinos.pdf');
}

public function destinosExcel(Request $request)
{
    return Excel::download(
        new DestinosExport($request->query('buscar')),
        'reporte_destinos.xlsx'
    );
}

public function inventarioPDF(Request $request)
{
    $inventarios = $this->inventarioQuery($request)->get();

    $pdf = Pdf::loadView('reportes.inventario_productos_pdf', compact('inventarios'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('reporte_inventario.pdf');
}

public function inventarioExcel(Request $request)
{
    return Excel::download(
        new InventarioProductosExport(
            $request->query('buscar'),
            $request->query('desde'),
            $request->query('hasta'),
            $request->query('tipo_producto'),
            $request->query('clasificacion')
        ),
        'reporte_inventario.xlsx'
    );
}

private function inventarioQuery(Request $request)
{
    $buscar = $request->query('buscar');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');
    $tipo = $request->query('tipo_producto');
    $clasificacion = $request->query('clasificacion');

    return InventarioProducto::query()
        ->with('producto')
        ->where('activo', true)
        ->when($desde, fn ($q) => $q->whereDate('fecha_registro', '>=', $desde))
        ->when($hasta, fn ($q) => $q->whereDate('fecha_registro', '<=', $hasta))
        ->when($tipo, fn ($q) => $q->whereHas('producto', fn ($p) => $p->where('tipo_producto', $tipo)))
        ->when($clasificacion, fn ($q) => $q->where('clasificacion', $clasificacion))
        ->when($buscar, function ($q) use ($buscar) {
            $q->where(function ($sub) use ($buscar) {
                $sub->where('clasificacion', 'ILIKE', "%{$buscar}%")
                    ->orWhereHas('producto', fn ($p) => $p
                        ->where('nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('codigo', 'ILIKE', "%{$buscar}%")
                        ->orWhere('tipo_producto', 'ILIKE', "%{$buscar}%"));
            });
        })
        ->orderByDesc('fecha_registro');
}

public function usuariosPDF(Request $request)
{
    $usuarios = $this->usuariosQuery($request)->get();

    $pdf = Pdf::loadView('reportes.usuarios_pdf', compact('usuarios'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('reporte_usuarios.pdf');
}

public function usuariosExcel(Request $request)
{
    return Excel::download(
        new UsuariosExport($request->query('buscar'), $request->boolean('activo', true)),
        'reporte_usuarios.xlsx'
    );
}

private function usuariosQuery(Request $request)
{
    $buscar = $request->query('buscar');

    return User::query()
        ->with('rol')
        ->where('activo', $request->boolean('activo', true))
        ->when($buscar, function ($query) use ($buscar) {
            $query->where(function ($sub) use ($buscar) {
                $sub->where('name', 'ILIKE', "%{$buscar}%")
                    ->orWhere('email', 'ILIKE', "%{$buscar}%")
                    ->orWhere('nro_carnet', 'ILIKE', "%{$buscar}%");
            });
        })
        ->orderBy('id');
}

public function distribucionesPDF(Request $request)
{
    $export = new DistribucionesExport($request->query('tipo'), $request->query('buscar'));
    $registros = $export->collection();
    $columnas = $export->columns();

    $pdf = Pdf::loadView('reportes.distribuciones_pdf', compact('registros', 'columnas'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('reporte_distribuciones.pdf');
}

public function distribucionesExcel(Request $request)
{
    return Excel::download(
        new DistribucionesExport($request->query('tipo'), $request->query('buscar')),
        'reporte_distribuciones.xlsx'
    );
}

public function reciboSalidaPDF(SalidaRevision $salidaRevision, Request $request)
{
    $salida = $salidaRevision->load([
        'detalles.producto',
        'detalles.lote',
        'distribuidor',
        'destino',
        'despachadoPor',
        'autorizadoPor',
    ]);

    $pdf = Pdf::loadView('reportes.recibo_salida_pdf', compact('salida'))
        ->setPaper([0, 0, 612.28, 396.85]);

    return $pdf->download(($salida->nro_informe ?: 'recibo_salida').'.pdf');
}

private function distribucionesRows(Request $request)
{
    return (new DistribucionesExport($request->query('tipo'), $request->query('buscar')))->collection();
}

public function lotesPDF(Request $request)
{
    $lotes = $this->lotesQuery($request)->get();
    $pdf = Pdf::loadView('reportes.lotes_pdf', compact('lotes'))->setPaper('a4', 'landscape');
    return $pdf->download('reporte_lotes.pdf');
}

public function lotesExcel(Request $request)
{
    return Excel::download(
        new LotesExport($request->query('buscar'), $request->query('estado'), $request->query('producto_id')),
        'reporte_lotes.xlsx'
    );
}

private function lotesQuery(Request $request)
{
    $buscar = $request->query('buscar');
    return Lote::query()
        ->with(['producto', 'linea', 'usuario'])
        ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado', $estado))
        ->when($request->query('producto_id'), fn ($q, $productoId) => $q->where('producto_id', $productoId))
        ->when($buscar, function ($q) use ($buscar) {
            $q->where('codigo_lote', 'ILIKE', "%{$buscar}%")
                ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"))
                ->orWhereHas('linea', fn ($l) => $l->where('nombre', 'ILIKE', "%{$buscar}%"));
        })
        ->orderByDesc('fecha_inicio');
}

public function lineasPDF(Request $request)
{
    $lineas = $this->lineasQuery($request)->get();
    $pdf = Pdf::loadView('reportes.lineas_pdf', compact('lineas'))->setPaper('a4', 'landscape');
    return $pdf->download('reporte_lineas.pdf');
}

public function lineasExcel(Request $request)
{
    return Excel::download(
        new LineasProduccionExport($request->query('buscar'), $request->query('estado')),
        'reporte_lineas.xlsx'
    );
}

private function lineasQuery(Request $request)
{
    $buscar = $request->query('buscar');
    return LineaProduccion::query()
        ->withCount('lotes')
        ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado', $estado))
        ->when($buscar, function ($q) use ($buscar) {
            $q->where('nombre', 'ILIKE', "%{$buscar}%")
                ->orWhere('turno', 'ILIKE', "%{$buscar}%")
                ->orWhere('descripcion', 'ILIKE', "%{$buscar}%");
        })
        ->orderBy('nombre');
}

public function trazabilidadPDF(Request $request)
{
    $registros = $this->trazabilidadQuery($request)->get();
    $pdf = Pdf::loadView('reportes.trazabilidad_pdf', compact('registros'))->setPaper('a4', 'landscape');
    return $pdf->download('reporte_trazabilidad.pdf');
}

public function trazabilidadExcel(Request $request)
{
    return Excel::download(
        new TrazabilidadExport($request->user(), $request->query('buscar'), $request->query('estado')),
        'reporte_trazabilidad.xlsx'
    );
}

private function trazabilidadQuery(Request $request)
{
    $buscar = $request->query('buscar');
    return Distribucion::query()
        ->with(['lote.producto', 'destino', 'responsable'])
        ->when((int) $request->user()->id_rol === 5, fn ($q) => $q->where('responsable_id', $request->user()->id))
        ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado', $estado))
        ->when($buscar, function ($q) use ($buscar) {
            $q->where('estado', 'ILIKE', "%{$buscar}%")
                ->orWhereHas('lote', fn ($l) => $l->where('codigo_lote', 'ILIKE', "%{$buscar}%"))
                ->orWhereHas('lote.producto', fn ($p) => $p->where('nombre', 'ILIKE', "%{$buscar}%"))
                ->orWhereHas('destino', fn ($d) => $d->where('nombre', 'ILIKE', "%{$buscar}%"))
                ->orWhereHas('responsable', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%"));
        })
        ->orderByDesc('fecha_envio');
}
}
