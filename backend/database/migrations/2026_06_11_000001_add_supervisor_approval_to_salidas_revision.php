<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salidas_revision', function (Blueprint $table) {
            $table->unsignedBigInteger('autorizado_por_id')->nullable()->after('despachado_por_id');
            $table->string('estado_aprobacion', 20)->default('aprobada')->after('observaciones');
            $table->timestamp('aprobado_en')->nullable()->after('estado_aprobacion');
            $table->timestamp('rechazado_en')->nullable()->after('aprobado_en');

            $table->foreign('autorizado_por_id')->references('id')->on('users');
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->unsignedBigInteger('salida_id')->nullable()->after('deteccion_id');
            $table->foreign('salida_id')->references('id_salida')->on('salidas_revision')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropForeign(['salida_id']);
            $table->dropColumn('salida_id');
        });

        Schema::table('salidas_revision', function (Blueprint $table) {
            $table->dropForeign(['autorizado_por_id']);
            $table->dropColumn(['autorizado_por_id', 'estado_aprobacion', 'aprobado_en', 'rechazado_en']);
        });
    }
};
