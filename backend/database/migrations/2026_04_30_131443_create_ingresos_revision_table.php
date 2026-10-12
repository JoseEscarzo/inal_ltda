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
    Schema::create('ingresos_revision', function (Blueprint $table) {
        $table->id('id_ingreso');
        $table->unsignedBigInteger('entregado_por_id');
        $table->unsignedBigInteger('recibido_por_id');
        $table->timestamp('fecha_hora')->useCurrent();
        $table->string('turno', 20)->nullable();
        $table->text('observaciones')->nullable();

        $table->foreign('entregado_por_id')->references('id')->on('users');
        $table->foreign('recibido_por_id')->references('id')->on('users');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos_revision');
    }
};
