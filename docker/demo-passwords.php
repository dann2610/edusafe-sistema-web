<?php
// Crea (si faltan) y fija la contraseña de los usuarios DEMO. Muestra el resultado en los logs.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pass = (string) getenv('DEMO_PASSWORD');
if ($pass === '') {
    echo ">> DEMO_PASSWORD vacía: no se cambian contraseñas\n";
    exit(0);
}

$emails = [
    'superadmin.demo@uecrc.test',
    'admin.demo@uecrc.test',
    'dece.demo@uecrc.test',
    'institucion.demo@uecrc.test',
    'policia.demo@uecrc.test',
    'admin@uedemo.test',
    'dece@uedemo.test',
    'institucion@uedemo.test',
];

foreach ($emails as $email) {
    try {
        $user = App\Models\User::where('email', $email)->first();
        if (! $user) {
            echo ">> NO EXISTE: {$email}\n";
            continue;
        }
        $user->password = $pass;
        $user->must_change_password = false;
        $user->is_active = true;
        $user->save();
        echo ">> OK: {$email}\n";
    } catch (Throwable $e) {
        echo ">> ERROR {$email}: ".$e->getMessage()."\n";
    }
}