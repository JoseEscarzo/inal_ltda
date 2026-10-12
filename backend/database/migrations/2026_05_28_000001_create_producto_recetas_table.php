<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE inventario_productos ALTER COLUMN cantidad TYPE NUMERIC(12,3) USING cantidad::numeric');
        DB::statement('ALTER TABLE inventario_productos ALTER COLUMN stock TYPE NUMERIC(12,3) USING stock::numeric');

        Schema::create('producto_recetas', function (Blueprint $table) {
            $table->id('id_receta');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('materia_prima_id');
            $table->decimal('cantidad_por_unidad', 12, 3);
            $table->decimal('cantidad_total', 12, 3)->default(0);
            $table->unsignedInteger('unidades_producidas')->default(100);
            $table->string('unidad', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('producto_id')->references('id_producto')->on('productos')->onDelete('cascade');
            $table->foreign('materia_prima_id')->references('id_producto')->on('productos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_recetas');

        DB::statement('ALTER TABLE inventario_productos ALTER COLUMN cantidad TYPE INTEGER USING cantidad::integer');
        DB::statement('ALTER TABLE inventario_productos ALTER COLUMN stock TYPE INTEGER USING stock::integer');
    }
};
