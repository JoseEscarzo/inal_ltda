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
    Schema::create('salidas_revision', function (Blueprint $table) {
        $table->id('id_salida');
        $table->string('nro_informe', 50)->unique();
        $table->unsignedBigInteger('despachado_por_id');
        $table->unsignedBigInteger('distribuidor_id');
        $table->unsignedBigInteger('destino_id')->nullable();
        $table->timestamp('fecha_hora')->useCurrent();
        $table->string('turno', 20)->nullable();
        $table->text('observaciones')->nullable();

        $table->foreign('despachado_por_id')->references('id')->on('users');
        $table->foreign('distribuidor_id')->references('id')->on('users');
        $table->foreign('destino_id')->references('id_destino')->on('destinos');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salidas_revision');
    }
};
