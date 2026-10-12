<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            // Volvemos al estándar en inglés: user_id conectado a la tabla users
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 128);
            $table->timestamps();
            $table->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};




