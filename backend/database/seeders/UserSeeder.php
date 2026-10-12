<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Jose Angel Mamani Escarzo',
            'email' => 'stichjose0525@gmail.com',
            'password' => Hash::make('Jose1234'),
            'id_rol' => 1,
        ]);
    }
}
