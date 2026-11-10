<?php

use Database\Seeders\RecargaDashboardSeeder;
use Illuminate\Support\Facades\Artisan;

// Carga datos de demostración adicionales para que el Dashboard tenga gráficas y KPIs.
// Uso: php artisan dashboard:demo
Artisan::command('dashboard:demo {--force : Permite ejecutarlo en producción}', function () {
    if (app()->isProduction() && ! $this->option('force')) {
        $this->error('Estás en producción. Este comando inserta datos ficticios; usa --force solo si estás seguro.');

        return 1;
    }

    $this->call('db:seed', ['--class' => RecargaDashboardSeeder::class, '--force' => true]);
    $this->info('Datos de demostración cargados. Abre /dashboard para verlos.');

    return 0;
})->purpose('Carga datos de demostración para el Dashboard (empresa demo #1)');
