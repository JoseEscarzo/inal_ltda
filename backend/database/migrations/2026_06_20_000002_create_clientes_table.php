<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('id_cliente');
            $table->unsignedBigInteger('destino_id')->nullable()->unique();
            $table->string('razon_social', 180);
            $table->string('nit_ci', 40)->nullable();
            $table->string('nombre_encargado', 180)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('correo', 180)->nullable();
            $table->text('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('destino_id')->references('id_destino')->on('destinos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
