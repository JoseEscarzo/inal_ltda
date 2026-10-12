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
    Schema::create('confirmaciones_alerta', function (Blueprint $table) {
        $table->id('id_confirmacion');
        $table->unsignedBigInteger('alerta_id')->nullable();
        $table->unsignedBigInteger('usuario_id')->nullable();
        $table->timestamp('fecha_confirmacion')->nullable();
        $table->text('observacion')->nullable();

        $table->foreign('alerta_id')->references('id_alerta')->on('alertas');
        $table->foreign('usuario_id')->references('id')->on('users');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmaciones_alerta');
    }
};
