<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pago');
            $table->unsignedBigInteger('factura_id');
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 40)->default('efectivo');
            $table->string('estado', 30)->default('pendiente');
            $table->timestamp('fecha_pago')->nullable();
            $table->string('referencia', 180)->nullable();
            $table->unsignedBigInteger('registrado_por_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('factura_id')->references('id_factura')->on('facturas')->cascadeOnDelete();
            $table->foreign('registrado_por_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
