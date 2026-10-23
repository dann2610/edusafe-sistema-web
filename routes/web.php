<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\QrController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CaseActionController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityReferralController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Support\CatalogRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Páginas públicas (destino del código QR)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/privacidad', [PublicController::class, 'privacy'])->name('public.privacy');
Route::get('/reportar', [PublicController::class, 'reportForm'])->name('public.report');
Route::post('/reportar', [PublicController::class, 'reportStore'])->middleware('throttle:public-report')->name('public.report.store');
Route::get('/reportar/enviado', [PublicController::class, 'reportSent'])->name('public.report.sent');

/*
|--------------------------------------------------------------------------
| Autenticación (solo visitantes)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Área privada: sesión + cuenta activa + contraseña personal.
| Cada módulo valida además su permiso ("can:") y las Policies por registro.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/cambiar-contrasena', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('/cambiar-contrasena', [ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('/panel', PanelController::class)->name('panel');
    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile.show');

    // Notificaciones internas
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificaciones/{id}/abrir', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notificaciones/{id}/leida', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notificaciones/leer-todas', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Registro de incidentes y reportes propios
    Route::middleware('can:incidentes.registrar')->group(function () {
        Route::get('/incidentes/registrar', [IncidentController::class, 'create'])->name('incidentes.create');
        Route::post('/incidentes', [IncidentController::class, 'store'])->name('incidentes.store');
    });
    Route::middleware('can:incidentes.ver_propios')->group(function () {
        Route::get('/mis-reportes', [IncidentController::class, 'myReports'])->name('reportes.mine');
        Route::get('/mis-reportes/{incident}', [IncidentController::class, 'showOwn'])->name('reportes.show');
    });

    // Evidencias (autorización por Policy)
    Route::get('/adjuntos/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');

    // Gestión de casos
    Route::get('/casos', [CaseController::class, 'inbox'])->middleware('can:casos.bandeja')->name('casos.inbox');
    Route::get('/casos/asignados', [CaseController::class, 'assigned'])->middleware('can:seguimientos.registrar')->name('casos.assigned');
    Route::get('/casos/{incident}', [CaseController::class, 'show'])->name('casos.show');
    Route::post('/casos/{incident}/estado', [CaseController::class, 'transition'])->name('casos.transition');
    Route::post('/casos/{incident}/prioridad', [CaseController::class, 'priority'])->name('casos.priority');
    Route::post('/casos/{incident}/involucrados', [CaseController::class, 'addParticipant'])->name('casos.participants.store');
    Route::post('/casos/{incident}/adjuntos', [CaseController::class, 'addAttachments'])->name('casos.attachments.store');
    Route::post('/casos/{incident}/asignaciones', [CaseActionController::class, 'assign'])->name('casos.assign');
    Route::patch('/casos/{incident}/asignaciones/{assignment}/finalizar', [CaseActionController::class, 'release'])->name('casos.assign.release');
    Route::post('/casos/{incident}/seguimientos', [CaseActionController::class, 'followUp'])->name('casos.followups.store');
    Route::post('/casos/{incident}/seguimientos/{followUp}/correccion', [CaseActionController::class, 'correctFollowUp'])->name('casos.followups.correct');
    Route::post('/casos/{incident}/medidas', [CaseActionController::class, 'storeMeasure'])->name('casos.measures.store');
    Route::patch('/casos/{incident}/medidas/{measure}', [CaseActionController::class, 'updateMeasure'])->name('casos.measures.update');
    Route::post('/casos/{incident}/derivaciones', [CaseActionController::class, 'storeReferral'])->name('casos.referrals.store');
    Route::patch('/casos/{incident}/derivaciones/{referral}', [CaseActionController::class, 'updateReferral'])->name('casos.referrals.update');

    // Entidades externas (personal policial)
    Route::middleware('can:derivaciones.ver_entidad')->group(function () {
        Route::get('/derivaciones', [EntityReferralController::class, 'index'])->name('entity.referrals.index');
        Route::get('/derivaciones/{referral}', [EntityReferralController::class, 'show'])->name('entity.referrals.show');
    });

    // Indicadores y reportes
    Route::get('/indicadores', DashboardController::class)->middleware('can:dashboard.ver')->name('dashboard');
    Route::middleware('can:reportes.generar')->group(function () {
        Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reportes/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reportes/excel', [ReportController::class, 'excel'])->name('reports.excel');
    });
    Route::get('/reportes/casos-excel', [ReportController::class, 'casesExcel'])
        ->middleware('can:reportes.exportar_identificable')->name('reports.cases-excel');

    // Bitácora
    Route::get('/bitacora', AuditLogController::class)->middleware('can:auditoria.consultar')->name('audit.index');

    // Administración técnica
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:usuarios.gestionar')->group(function () {
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

        Route::middleware('can:catalogos.gestionar')->group(function () {
            Route::view('/catalogos', 'admin.catalogs.home')->name('catalogos.home');

            Route::get('/periodos', [AcademicPeriodController::class, 'index'])->name('periodos.index');
            Route::get('/periodos/crear', [AcademicPeriodController::class, 'create'])->name('periodos.create');
            Route::post('/periodos', [AcademicPeriodController::class, 'store'])->name('periodos.store');
            Route::get('/periodos/{period}/editar', [AcademicPeriodController::class, 'edit'])->name('periodos.edit');
            Route::put('/periodos/{period}', [AcademicPeriodController::class, 'update'])->name('periodos.update');
            Route::patch('/periodos/{period}/vigente', [AcademicPeriodController::class, 'makeCurrent'])->name('periodos.current');
            Route::patch('/periodos/{period}/estado', [AcademicPeriodController::class, 'toggle'])->name('periodos.toggle');

            Route::get('/cursos', [CourseController::class, 'index'])->name('cursos.index');
            Route::get('/cursos/crear', [CourseController::class, 'create'])->name('cursos.create');
            Route::post('/cursos', [CourseController::class, 'store'])->name('cursos.store');
            Route::get('/cursos/{course}/editar', [CourseController::class, 'edit'])->name('cursos.edit');
            Route::put('/cursos/{course}', [CourseController::class, 'update'])->name('cursos.update');
            Route::patch('/cursos/{course}/estado', [CourseController::class, 'toggle'])->name('cursos.toggle');

            Route::get('/estudiantes', [StudentController::class, 'index'])->name('estudiantes.index');
            Route::get('/estudiantes/crear', [StudentController::class, 'create'])->name('estudiantes.create');
            Route::post('/estudiantes', [StudentController::class, 'store'])->name('estudiantes.store');
            Route::get('/estudiantes/importar', [StudentController::class, 'importForm'])->name('estudiantes.import');
            Route::post('/estudiantes/importar', [StudentController::class, 'import'])->name('estudiantes.import.store');
            Route::get('/estudiantes/{student}/editar', [StudentController::class, 'edit'])->name('estudiantes.edit');
            Route::put('/estudiantes/{student}', [StudentController::class, 'update'])->name('estudiantes.update');
            Route::patch('/estudiantes/{student}/estado', [StudentController::class, 'toggle'])->name('estudiantes.toggle');

            $catalogs = implode('|', CatalogRegistry::keys());
            Route::get('/catalogos/{catalog}', [CatalogController::class, 'index'])->where('catalog', $catalogs)->name('catalogos.index');
            Route::get('/catalogos/{catalog}/crear', [CatalogController::class, 'create'])->where('catalog', $catalogs)->name('catalogos.create');
            Route::post('/catalogos/{catalog}', [CatalogController::class, 'store'])->where('catalog', $catalogs)->name('catalogos.store');
            Route::get('/catalogos/{catalog}/{id}/editar', [CatalogController::class, 'edit'])->where(['catalog' => $catalogs, 'id' => '[0-9]+'])->name('catalogos.edit');
            Route::put('/catalogos/{catalog}/{id}', [CatalogController::class, 'update'])->where(['catalog' => $catalogs, 'id' => '[0-9]+'])->name('catalogos.update');
            Route::patch('/catalogos/{catalog}/{id}/estado', [CatalogController::class, 'toggle'])->where(['catalog' => $catalogs, 'id' => '[0-9]+'])->name('catalogos.toggle');
        });

        Route::middleware('can:configuracion.gestionar')->group(function () {
            Route::get('/qr', [QrController::class, 'show'])->name('qr.show');
            Route::get('/qr/descargar/{format}', [QrController::class, 'download'])->whereIn('format', ['png', 'svg'])->name('qr.download');
        });
    });
});
