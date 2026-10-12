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
    Schema::create('reportes', function (Blueprint $table) {
        $table->id('id_reporte');
        $table->unsignedBigInteger('generado_por')->nullable();
        $table->string('tipo', 100)->nullable();
        $table->jsonb('parametros')->nullable();
        $table->timestamp('fecha_generacion')->nullable();
        $table->string('formato', 20)->nullable();
        $table->string('ruta_archivo', 300)->nullable();

        $table->foreign('generado_por')->references('id')->on('users');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
