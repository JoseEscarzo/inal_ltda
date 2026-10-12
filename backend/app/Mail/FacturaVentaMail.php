<?php

namespace App\Mail;

use App\Models\Factura;
use App\Support\MailFromAddress;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacturaVentaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Factura $factura) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: MailFromAddress::envelopeFrom(),
            subject: 'Factura INAL Ltda. #'.$this->factura->numero_factura,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.factura_venta',
            with: ['factura' => $this->factura]
        );
    }

    public function attachments(): array
    {
        $factura = $this->factura->loadMissing([
            'cliente',
            'emitidoPor',
            'distribuidor',
            'salida.destino',
            'detalles.producto',
            'detalles.lote',
        ]);

        $pdf = Pdf::loadView('facturas.pdf', [
            'factura' => $factura,
            'nitEmpresa' => '1020693021',
            'qrImageUrl' => $this->qrDataUri($factura->url_qr),
            'logoImageUrl' => $this->imageDataUri(public_path('images/helados.png')),
        ])->setOptions(['isRemoteEnabled' => true])
            ->setPaper('a4', 'portrait')
            ->output();

        return [
            Attachment::fromData(fn () => $pdf, 'factura_'.$factura->numero_factura.'.pdf')
                ->withMime('application/pdf'),
        ];
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
