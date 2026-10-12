<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id_rol' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema, gestiona usuarios, configuraciones y procesos'],
            ['id_rol' => 2, 'nombre' => 'Supervisor', 'descripcion' => 'Supervisa inventario, monitoreo, reportes, detecciones, camaras, alertas, productos y destinos'],
            ['id_rol' => 3, 'nombre' => 'Operador', 'descripcion' => 'Ejecuta monitoreo en tiempo real, detecciones, alertas, produccion, camaras y modelos de IA'],
            ['id_rol' => 4, 'nombre' => 'Analista', 'descripcion' => 'Analiza datos, genera reportes y encargado del marketing'],
            ['id_rol' => 5, 'nombre' => 'Distribuidor', 'descripcion' => 'Encargado de gestionar destinos y distribuciones de productos'],
        ];

        Rol::insert($roles);
    }
}
