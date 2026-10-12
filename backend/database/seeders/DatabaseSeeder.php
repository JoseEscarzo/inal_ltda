<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Eliminamos el 'Test User' y llamamos a tus dos seeders en este orden estricto:
        $this->call([
            RolSeeder::class,
            UserSeeder::class,
        ]);
    }
}
