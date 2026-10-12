<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alertas = Alerta::query()
            ->with([
                'salida.detalles.producto',
                'salida.detalles.lote',
                'salida.despachadoPor',
                'salida.autorizadoPor',
                'salida.distribuidor',
                'salida.destino',
            ])
            ->where('usuario_destino_id', $request->user()->id)
            ->where('estado', 'pendiente')
            ->orderByDesc('fecha_hora')
            ->get();

        return response()->json(['alertas' => $alertas]);
    }
}
