<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Pago;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function __construct(private PagoService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(Pago::with(['factura.cliente', 'registradoPor'])->orderByDesc('fecha_pago')->paginate(25));
    }

    public function store(Request $request, Factura $factura): JsonResponse
    {
        $data = $request->validate([
            'monto' => 'nullable|numeric|min:0',
            'metodo' => 'nullable|string|max:40',
            'estado' => 'nullable|in:pendiente,pagado,anulado',
            'fecha_pago' => 'nullable|date',
            'referencia' => 'nullable|string|max:120',
        ]);

        return response()->json($this->service->registrar($factura, $data, $request->user()), 201);
    }
}
