<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id('id_proveedor');
            $table->string('nombre', 150);
            $table->string('nit', 40)->nullable();
            $table->string('contacto', 150)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('correo', 180)->nullable();
            $table->text('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('ingresos_revision', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->nullable()->after('entregado_por_id');
            $table->foreign('proveedor_id')->references('id_proveedor')->on('proveedores');
        });
    }

    public function down(): void
    {
        Schema::table('ingresos_revision', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });

        Schema::dropIfExists('proveedores');
    }
};
