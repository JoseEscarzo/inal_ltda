<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('salida_detalles', function (Blueprint $table) {
        $table->id('id_detalle_salida');
        $table->unsignedBigInteger('salida_id');
        $table->unsignedBigInteger('producto_id');
        $table->unsignedBigInteger('lote_id')->nullable();
        $table->integer('cantidad');

        $table->foreign('salida_id')->references('id_salida')->on('salidas_revision')->onDelete('cascade');
        $table->foreign('producto_id')->references('id_producto')->on('productos');
        $table->foreign('lote_id')->references('id_lote')->on('lotes');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salida_detalles');
    }
};
