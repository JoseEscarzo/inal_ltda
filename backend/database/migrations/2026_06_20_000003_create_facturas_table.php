<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id('id_factura');
            $table->unsignedBigInteger('salida_id')->nullable();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('emitido_por_id')->nullable();
            $table->unsignedBigInteger('distribuidor_id')->nullable();
            $table->integer('numero_factura')->unique();
            $table->string('codigo_autorizacion', 160)->nullable();
            $table->timestamp('fecha_emision');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total_pagar', 12, 2)->default(0);
            $table->string('estado', 30)->default('emitida');
            $table->text('codigo_qr')->nullable();
            $table->text('url_qr')->nullable();
            $table->string('metodo_pago', 40)->default('efectivo');
            $table->string('estado_pago', 30)->default('pendiente');
            $table->timestamp('correo_enviado_at')->nullable();
            $table->string('correo_destino', 180)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('salida_id')->references('id_salida')->on('salidas_revision')->nullOnDelete();
            $table->foreign('cliente_id')->references('id_cliente')->on('clientes');
            $table->foreign('emitido_por_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('distribuidor_id')->references('id')->on('users')->nullOnDelete();
            $table->unique('salida_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
