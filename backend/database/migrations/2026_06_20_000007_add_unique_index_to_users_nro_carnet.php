<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS users_nro_carnet_unique_not_null
            ON users (nro_carnet)
            WHERE nro_carnet IS NOT NULL AND nro_carnet <> ''
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_nro_carnet_unique_not_null');
    }
};
