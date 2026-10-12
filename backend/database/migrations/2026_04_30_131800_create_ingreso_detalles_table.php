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
    Schema::create('ingreso_detalles', function (Blueprint $table) {
        $table->id('id_detalle_ingreso');
        $table->unsignedBigInteger('ingreso_id');
        $table->unsignedBigInteger('producto_id');
        $table->unsignedBigInteger('lote_id')->nullable();
        $table->integer('cantidad');
        $table->integer('cantidad_aprobada')->default(0)->after('cantidad');
        $table->integer('cantidad_rechazada')->default(0)->after('cantidad_aprobada');

        $table->foreign('ingreso_id')->references('id_ingreso')->on('ingresos_revision')->onDelete('cascade');
        $table->foreign('producto_id')->references('id_producto')->on('productos');
        $table->foreign('lote_id')->references('id_lote')->on('lotes');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingreso_detalles', function (Blueprint $table) {
            $table->dropColumn(['cantidad_aprobada', 'cantidad_rechazada']);
        });
    }
};
