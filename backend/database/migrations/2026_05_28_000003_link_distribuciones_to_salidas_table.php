<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribuciones', function (Blueprint $table) {
            $table->unsignedBigInteger('salida_id')->nullable()->after('responsable_id');
            $table->unsignedBigInteger('salida_detalle_id')->nullable()->after('salida_id');

            $table->foreign('salida_id')->references('id_salida')->on('salidas_revision')->onDelete('cascade');
            $table->foreign('salida_detalle_id')->references('id_detalle_salida')->on('salida_detalles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('distribuciones', function (Blueprint $table) {
            $table->dropForeign(['salida_id']);
            $table->dropForeign(['salida_detalle_id']);
            $table->dropColumn(['salida_id', 'salida_detalle_id']);
        });
    }
};
