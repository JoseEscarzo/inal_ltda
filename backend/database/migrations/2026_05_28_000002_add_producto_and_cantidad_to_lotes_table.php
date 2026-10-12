<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable()->after('codigo_lote');
            $table->decimal('cantidad', 12, 3)->default(0)->after('usuario_id');
            $table->text('observaciones')->nullable()->after('estado');

            $table->foreign('producto_id')->references('id_producto')->on('productos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'cantidad', 'observaciones']);
        });
    }
};
