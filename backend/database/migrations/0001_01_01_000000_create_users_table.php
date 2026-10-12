<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de ROLES
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre', 50)->nullable();
            $table->text('descripcion')->nullable();
        });

        // 2. Tabla de USUARIOS (users)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Campos extra de tu base de datos PostgreSQL
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('phone', 8)->nullable();

            $table->date('fecha_nacimiento');
            $table->string('nro_carnet', 20)->unique();
            $table->boolean('activo')->default(true);


            $table->text('face_photo_base64')->nullable();
            $table->json('face_descriptor')->nullable();
            $table->string('login_otp_code', 6)->nullable();
            $table->timestamp('login_otp_expires_at')->nullable();

            // Relación con Roles
            $table->unsignedBigInteger('id_rol')->nullable();
            $table->foreign('id_rol')->references('id_rol')->on('roles');
        });

        // Tabla por defecto de Laravel para reseteo de contraseñas
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Tabla de SESSIONS
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
