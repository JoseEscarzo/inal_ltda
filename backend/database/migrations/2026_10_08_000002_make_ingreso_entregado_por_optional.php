<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Un ingreso puede ser entregado por un proveedor, no necesariamente por un usuario del sistema.
        DB::statement('ALTER TABLE ingresos_revision ALTER COLUMN entregado_por_id DROP NOT NULL');
    }

    public function down(): void
    {
        // No se revierte porque pueden existir ingresos válidos asociados únicamente a proveedores.
    }
};
