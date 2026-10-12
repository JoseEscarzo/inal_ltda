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
    Schema::create('modelos_deteccion', function (Blueprint $table) {
        $table->id('id_modelo');
        $table->string('nombre', 100)->nullable();
        $table->string('version', 20)->nullable();
        $table->string('ruta_archivo', 300)->nullable();
        $table->decimal('umbral_confianza', 5, 2)->nullable();
        $table->boolean('activo')->nullable();
        $table->text('descripcion')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelos_deteccion');
    }
};
