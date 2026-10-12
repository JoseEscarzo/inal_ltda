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
    Schema::create('alertas', function (Blueprint $table) {
        $table->id('id_alerta');
        $table->unsignedBigInteger('deteccion_id')->nullable();
        $table->unsignedBigInteger('usuario_destino_id')->nullable();
        $table->string('nivel', 20)->nullable();
        $table->timestamp('fecha_hora')->nullable();
        $table->string('estado', 20)->nullable();
        $table->text('mensaje')->nullable();
        $table->string('ruta_evidencia', 300)->nullable();

        $table->foreign('deteccion_id')->references('id_deteccion')->on('detecciones');
        $table->foreign('usuario_destino_id')->references('id')->on('users');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
