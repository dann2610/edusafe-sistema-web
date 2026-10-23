<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
| La página pública de bienvenida y el formulario de reporte se construyen
| en el Sprint 3. Mientras tanto, la raíz lleva al inicio de sesión.
*/
Route::redirect('/', '/login');

/*
|--------------------------------------------------------------------------
| Autenticación (solo visitantes)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1')->name('login.store');

    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'email'])
        ->middleware('throttle:password-reset')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])
        ->middleware('throttle:10,1')->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Área privada
|--------------------------------------------------------------------------
| auth: sesión iniciada · active: cuenta activa con rol · password.changed:
| sin contraseña temporal pendiente. Los permisos se validan con "can:".
*/
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/cambiar-contrasena', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('/cambiar-contrasena', [ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('/panel', PanelController::class)->name('panel');
    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile.show');

    // Administración técnica
    Route::prefix('admin')->name('admin.')->middleware('can:usuarios.gestionar')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{user}', [UserController::class, 'show'])->name('usuarios.show');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{user}/desactivar', [UserController::class, 'deactivate'])->name('usuarios.deactivate');
        Route::patch('/usuarios/{user}/activar', [UserController::class, 'activate'])->name('usuarios.activate');
        Route::post('/usuarios/{user}/contrasena-temporal', [UserController::class, 'resetPassword'])->name('usuarios.reset-password');
    });
});
