<?php

use Illuminate\Support\Facades\Schedule;

/*
| Tareas programadas. Para ejecutarlas:
|   - Desarrollo: php artisan schedule:work
|   - Servidor Linux: cron "* * * * * php /ruta/artisan schedule:run"
|   - Windows: Programador de tareas ejecutando "php artisan schedule:run" cada minuto
*/
Schedule::command('tic:recordatorios-seguimiento')->dailyAt('07:00')->withoutOverlapping();
