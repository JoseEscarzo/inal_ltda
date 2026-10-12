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
    Schema::create('productos', function (Blueprint $table) {
        $table->id('id_producto');
        $table->string('nombre', 150)->nullable();
        $table->string('codigo', 50)->unique()->nullable();
        $table->text('descripcion')->nullable();
        $table->string('categoria', 100)->nullable();
        $table->string('tipo_producto', 100)->nullable();
        $table->string('presentacion', 100)->nullable();
        $table->boolean('activo')->default(true)->after('presentacion');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
