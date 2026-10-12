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
    Schema::create('lineas_produccion', function (Blueprint $table) {
        $table->id('id_linea');
        $table->string('nombre', 100)->nullable();
        $table->text('descripcion')->nullable();
        $table->string('estado', 20)->nullable();
        $table->string('turno', 20)->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lineas_produccion');
    }
};
