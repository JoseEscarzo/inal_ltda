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
    Schema::create('camaras', function (Blueprint $table) {
        $table->id('id_camara');
        $table->string('nombre', 100)->nullable();
        $table->unsignedBigInteger('linea_id')->nullable();
        $table->string('url_stream', 300)->nullable();
        $table->string('estado', 20)->nullable();
        $table->string('ubicacion', 200)->nullable();
        $table->timestamp('created_at')->useCurrent();

        $table->foreign('linea_id')->references('id_linea')->on('lineas_produccion');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camaras');
    }
};
