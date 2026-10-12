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
    Schema::create('detecciones', function (Blueprint $table) {
        $table->id('id_deteccion');
        $table->unsignedBigInteger('camara_id')->nullable();
        $table->unsignedBigInteger('modelo_id')->nullable();
        $table->unsignedBigInteger('tipo_defecto_id')->nullable();
        $table->unsignedBigInteger('lote_id')->nullable();
        $table->timestamp('fecha_hora')->nullable();
        $table->decimal('nivel_confianza', 5, 2)->nullable();
        $table->string('resultado', 20)->nullable();
        $table->string('ruta_imagen', 300)->nullable();

        $table->foreign('camara_id')->references('id_camara')->on('camaras');
        $table->foreign('modelo_id')->references('id_modelo')->on('modelos_deteccion');
        $table->foreign('tipo_defecto_id')->references('id_tipo_defecto')->on('tipos_defecto');
        $table->foreign('lote_id')->references('id_lote')->on('lotes');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detecciones');
    }
};
