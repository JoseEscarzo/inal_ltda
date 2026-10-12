<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('facturas')
            ->whereNull('codigo_autorizacion')
            ->orWhere('codigo_autorizacion', 'like', 'INAL-DEMO-%')
            ->orderBy('id_factura')
            ->get(['id_factura'])
            ->each(function ($factura) {
                DB::table('facturas')
                    ->where('id_factura', $factura->id_factura)
                    ->update([
                        'codigo_autorizacion' => Str::upper(str_replace('-', '', (string) Str::uuid()).Str::random(18)),
                    ]);
            });
    }

    public function down(): void
    {
        // No se revierte para no repetir codigos de autorizacion.
    }
};
