<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Producción: solo roles, permisos y catálogos (sin usuarios ni datos personales).
     * El administrador inicial se crea con: php artisan tic:crear-admin
     * Local: además se cargan datos DEMO claramente identificados.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CatalogSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([DemoDataSeeder::class, DemoIncidentsSeeder::class]);
        }
    }
}
