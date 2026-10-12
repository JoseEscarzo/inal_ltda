<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_factura', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('factura_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('lote_id')->nullable();
            $table->decimal('cantidad', 12, 3);
            $table->decimal('precio_venta', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->foreign('factura_id')->references('id_factura')->on('facturas')->cascadeOnDelete();
            $table->foreign('producto_id')->references('id_producto')->on('productos');
            $table->foreign('lote_id')->references('id_lote')->on('lotes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_factura');
    }
};
