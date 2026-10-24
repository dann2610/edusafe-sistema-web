<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Producción: institución inicial, módulos, roles, permisos y catálogos globales
     * (sin usuarios ni datos personales).
     * El administrador inicial se crea con: php artisan tic:crear-admin
     * Local: además se cargan datos DEMO claramente identificados.
     */
    public function run(): void
    {
        $this->call([
            PlatformSeeder::class,
            RolesAndPermissionsSeeder::class,
            CatalogSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([DemoDataSeeder::class, DemoIncidentsSeeder::class]);
        }
    }
}
