<?php

namespace App\Http\Controllers;

use App\DTOs\FacturaDesdeSalidaDTO;
use App\Http\Requests\StoreFacturaFromSalidaRequest;
use App\Models\Factura;
use App\Models\SalidaRevision;
use App\Services\FacturaService;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function __construct(private FacturaService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listar());
    }

    public function storeFromSalida(StoreFacturaFromSalidaRequest $request, SalidaRevision $salidaRevision): JsonResponse
    {
        $dto = FacturaDesdeSalidaDTO::fromArray($request->validated());
        return response()->json($this->service->crearDesdeSalida($salidaRevision, $request->user(), $dto->toArray()), 201);
    }

    public function show(Factura $factura): JsonResponse
    {
        return response()->json($factura->load($this->service->relaciones()));
    }

    public function pdf(Factura $factura)
    {
        return $this->descargarPdf($factura, 'facturas.pdf', 'factura_'.$factura->numero_factura.'.pdf', 'a4');
    }

    public function ticket(Factura $factura)
    {
        return $this->descargarPdf($factura, 'facturas.ticket', 'factura_ticket_'.$factura->numero_factura.'.pdf', [0, 0, 240, 868]);
    }

    public function enviarCorreo(Request $request, Factura $factura): JsonResponse
    {
        $data = $request->validate([
            'correo_destino' => 'nullable|email|max:180',
            'forzar' => 'nullable|boolean',
        ]);

        $enviado = $this->service->enviarPorCorreo(
            $factura,
            $data['correo_destino'] ?? null,
            (bool) ($data['forzar'] ?? false)
        );

        return response()->json([
            'enviado' => $enviado,
            'message' => $enviado ? 'Factura enviada por correo.' : 'El cliente no tiene correo registrado.',
            'factura' => $factura->fresh()->load($this->service->relaciones()),
        ]);
    }

    public function verificar(string $codigo)
    {
        $factura = Factura::with($this->service->relaciones())
            ->where('codigo_qr', $codigo)
            ->firstOrFail();

        return view('facturas.verificar', [
            'factura' => $factura,
            'nitEmpresa' => '1020693021',
        ]);
    }

    private function descargarPdf(Factura $factura, string $vista, string $nombre, $papel)
    {
        $factura->loadMissing($this->service->relaciones());

        $pdf = Pdf::loadView($vista, [
            'factura' => $factura,
            'nitEmpresa' => '1020693021',
            'qrImageUrl' => $this->qrDataUri($factura->url_qr),
            'logoImageUrl' => $this->imageDataUri(public_path('images/helados.png')),
        ])->setOptions(['isRemoteEnabled' => true]);

        if ($papel === 'a4') {
            $pdf->setPaper('a4', 'portrait');
        } else {
            $pdf->setPaper($papel, 'portrait');
        }

        return $pdf->download($nombre);
    }

    private function qrDataUri(string $texto): string
    {
        return (new QRCode(new QROptions([
            'outputType' => 'png',
            'scale' => 4,
            'imageBase64' => true,
        ])))->render($texto);
    }

    private function imageDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
