<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_costo', 10, 2)->default(0)->after('presentacion');
            $table->decimal('precio_distribuidor', 10, 2)->default(0)->after('precio_costo');
            $table->decimal('precio_venta_publico', 10, 2)->default(0)->after('precio_distribuidor');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio_costo', 'precio_distribuidor', 'precio_venta_publico']);
        });
    }
};
