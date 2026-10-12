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
    Schema::create('lotes', function (Blueprint $table) {
        $table->id('id_lote');
        $table->string('codigo_lote', 50)->unique()->nullable();
        $table->unsignedBigInteger('linea_id')->nullable();
        $table->unsignedBigInteger('usuario_id')->nullable();
        $table->timestamp('fecha_inicio')->nullable();
        $table->timestamp('fecha_fin')->nullable();
        $table->string('turno', 20)->nullable();
        $table->string('estado', 20)->nullable();

        $table->foreign('linea_id')->references('id_linea')->on('lineas_produccion');
        $table->foreign('usuario_id')->references('id')->on('users');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
