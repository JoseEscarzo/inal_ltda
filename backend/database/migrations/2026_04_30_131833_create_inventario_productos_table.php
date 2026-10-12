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
    Schema::create('inventario_productos', function (Blueprint $table) {
        $table->id('id_inventario');
        $table->unsignedBigInteger('deteccion_id')->nullable();
        $table->unsignedBigInteger('producto_id')->nullable();
        $table->unsignedBigInteger('lote_id')->nullable();
        $table->string('clasificacion', 20)->nullable();
        $table->string('ruta_evidencia', 300)->nullable();
        $table->timestamp('fecha_registro')->nullable();
        $table->integer('cantidad')->default(0)->after('clasificacion');
        $table->unsignedBigInteger('ingreso_detalle_id')->nullable()->after('cantidad');
        $table->integer('stock')->default(0);
        $table->boolean('activo')->default(true);

        $table->foreign('ingreso_detalle_id')->references('id_detalle_ingreso')->on('ingreso_detalles')->onDelete('set null');

        $table->foreign('deteccion_id')->references('id_deteccion')->on('detecciones');
        $table->foreign('producto_id')->references('id_producto')->on('productos');
        $table->foreign('lote_id')->references('id_lote')->on('lotes');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_productos', function (Blueprint $table) {
            $table->dropForeign(['ingreso_detalle_id']);
            $table->dropForeign(['deteccion_id']);
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['lote_id']);
        });
        Schema::dropIfExists('inventario_productos');
    }
};
