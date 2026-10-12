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
    Schema::create('distribuciones', function (Blueprint $table) {
        $table->id('id_distribucion');
        $table->unsignedBigInteger('lote_id')->nullable();
        $table->unsignedBigInteger('destino_id')->nullable();
        $table->unsignedBigInteger('responsable_id')->nullable();
        $table->timestamp('fecha_envio')->nullable();
        $table->integer('cantidad_productos')->nullable();
        $table->string('estado', 20)->nullable();
        $table->text('observaciones')->nullable();

        $table->foreign('lote_id')->references('id_lote')->on('lotes');
        $table->foreign('destino_id')->references('id_destino')->on('destinos');
        $table->foreign('responsable_id')->references('id')->on('users');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribuciones');
    }
};
