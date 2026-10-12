<?php

namespace App\Services;

/**
 * Normaliza la foto del rostro para guardarla en PostgreSQL (TEXT / data URI).
 * No modifica la configuración del sistema ni archivos del usuario.
 */
class FacePhotoEncodingService
{
    /**
     * Acepta data URI (data:image/jpeg;base64,...) o solo la parte base64
     * y devuelve siempre un data URI JPEG listo para almacenar.
     */
    public function toDataUriForStorage(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            return '';
        }

        if (str_starts_with($input, 'data:image/')) {
            return $input;
        }

        return 'data:image/jpeg;base64,'.$input;
    }

    /**
     * Extrae solo la cadena base64 (sin prefijo MIME) para logs o APIs que lo requieran.
     */
    public function extractBase64Payload(string $dataUriOrRaw): string
    {
        $dataUriOrRaw = trim($dataUriOrRaw);

        if (preg_match('#^data:image/[^;]+;base64,(.+)$#s', $dataUriOrRaw, $m)) {
            return $m[1];
        }

        return $dataUriOrRaw;
    }
}
