<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\InstitutionSettingsController;
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
use App\Http\Controllers\Platform\InstitutionController;
use App\Http\Controllers\Platform\ModuleController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Support\CatalogRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Páginas públicas (destino del código QR)
|--------------------------------------------------------------------------
| /i/{slug}/...  → página de cada institución.
| /, /reportar…  → atajos a la institución por defecto.
| Cada ruta del formulario exige el módulo correspondiente activo.
*/
$publicRoutes = function (string $prefix) {
    Route::get('/', [PublicController::class, 'home'])->name($prefix.'home');
    Route::get('/privacidad', [PublicController::class, 'privacy'])->name($prefix.'privacy');
    Route::middleware('module:reporte_publico')->group(function () use ($prefix) {
        Route::get('/reportar', [PublicController::class, 'reportForm'])->name($prefix.'report');
        Route::post('/reportar', [PublicController::class, 'reportStore'])->middleware('throttle:public-report')->name($prefix.'report.store');
        Route::get('/reportar/enviado', [PublicController::class, 'reportSent'])->name($prefix.'report.sent');
    });
    Route::middleware('module:consulta_estado')->group(function () use ($prefix) {
        Route::get('/consultar', [PublicController::class, 'statusForm'])->name($prefix.'status');
        Route::post('/consultar', [PublicController::class, 'statusLookup'])->middleware('throttle:status-lookup')->name($prefix.'status.lookup');
    });
};
Route::middleware('public.institution')->name('public.')->group(fn () => $publicRoutes(''));
Route::middleware('public.institution')->prefix('i/{institution}')->name('inst.')->group(fn () => $publicRoutes(''));
Route::get('/instituciones', [PublicController::class, 'directory'])->name('public.directory');

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
| Área privada: sesión + cuenta e institución activas + contraseña personal.
| Cada módulo valida además su permiso ("can:"), su activación ("module:")
| y las Policies por registro (incluida la pertenencia a la institución).
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
        Route::post('/mis-reportes/{incident}/informacion', [IncidentController::class, 'addNote'])->name('reportes.notes.store');
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
    Route::post('/casos/{incident}/escalar', [CaseActionController::class, 'escalate'])->name('casos.escalate');
    Route::post('/casos/{incident}/seguimientos', [CaseActionController::class, 'followUp'])->name('casos.followups.store');
    Route::post('/casos/{incident}/seguimientos/{followUp}/correccion', [CaseActionController::class, 'correctFollowUp'])->name('casos.followups.correct');
    Route::middleware('module:medidas')->group(function () {
        Route::post('/casos/{incident}/medidas', [CaseActionController::class, 'storeMeasure'])->name('casos.measures.store');
        Route::patch('/casos/{incident}/medidas/{measure}', [CaseActionController::class, 'updateMeasure'])->name('casos.measures.update');
    });
    Route::middleware('module:derivaciones')->group(function () {
        Route::post('/casos/{incident}/derivaciones', [CaseActionController::class, 'storeReferral'])->name('casos.referrals.store');
        Route::patch('/casos/{incident}/derivaciones/{referral}', [CaseActionController::class, 'updateReferral'])->name('casos.referrals.update');
    });

    // Entidades externas (personal policial)
    Route::middleware(['can:derivaciones.ver_entidad', 'module:derivaciones'])->group(function () {
        Route::get('/derivaciones', [EntityReferralController::class, 'index'])->name('entity.referrals.index');
        Route::get('/derivaciones/{referral}', [EntityReferralController::class, 'show'])->name('entity.referrals.show');
    });

    // Indicadores y reportes
    Route::get('/indicadores', DashboardController::class)->middleware(['can:dashboard.ver', 'module:dashboard'])->name('dashboard');
    Route::middleware(['can:reportes.generar', 'module:reportes'])->group(function () {
        Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reportes/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reportes/excel', [ReportController::class, 'excel'])->name('reports.excel');
    });
    Route::get('/reportes/casos-excel', [ReportController::class, 'casesExcel'])
        ->middleware(['can:reportes.exportar_identificable', 'module:reportes'])->name('reports.cases-excel');

    // Bitácora (cada administrador ve la de su institución; el superadministrador, toda)
    Route::get('/bitacora', AuditLogController::class)->middleware('can:auditoria.consultar')->name('audit.index');

    $catalogs = implode('|', CatalogRegistry::keys());
    $catalogRoutes = function () use ($catalogs) {
        Route::get('/catalogos/{catalog}', [CatalogController::class, 'index'])->where('catalog', $catalogs)->name('catalogos.index');
        Route::get('/catalogos/{catalog}/crear', [CatalogController::class, 'create'])->where('catalog', $catalogs)->name('catalogos.create');
        Route::post('/catalogos/{catalog}', [CatalogController::class, 'store'])->where('catalog', $catalogs)->name('catalogos.store');
        Route::get('/catalogos/{catalog}/{id}/editar', [CatalogController::class, 'edit'])->where(['catalog' => $catalogs, 'id' => '[0-9]+'])->name('catalogos.edit');
        Route::put('/catalogos/{catalog}/{id}', [CatalogController::class, 'update'])->where(['catalog' => $catalogs, 'id' => '[0-9]+'])->name('catalogos.update');
        Route::patch('/catalogos/{catalog}/{id}/estado', [CatalogController::class, 'toggle'])->where(['catalog' => $catalogs, 'id' => '[0-9]+'])->name('catalogos.toggle');

        Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categorias/crear', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categorias/{id}', [CategoryController::class, 'edit'])->whereNumber('id')->name('categories.edit');
        Route::put('/categorias/{id}', [CategoryController::class, 'update'])->whereNumber('id')->name('categories.update');
        Route::patch('/categorias/{id}/estado', [CategoryController::class, 'toggle'])->whereNumber('id')->name('categories.toggle');
        Route::post('/categorias/{id}/campos', [CategoryController::class, 'storeField'])->whereNumber('id')->name('categories.fields.store');
        Route::put('/categorias/{id}/campos/{field}', [CategoryController::class, 'updateField'])->whereNumber('id')->name('categories.fields.update');
        Route::patch('/categorias/{id}/campos/{field}/estado', [CategoryController::class, 'toggleField'])->whereNumber('id')->name('categories.fields.toggle');
    };

    // Plataforma (superadministrador)
    Route::prefix('plataforma')->name('platform.')->group(function () use ($catalogRoutes) {
        Route::get('/', PlatformDashboardController::class)->middleware('can:plataforma.estadisticas')->name('dashboard');
        Route::middleware('can:plataforma.instituciones')->group(function () {
            Route::get('/instituciones', [InstitutionController::class, 'index'])->name('institutions.index');
            Route::get('/instituciones/crear', [InstitutionController::class, 'create'])->name('institutions.create');
            Route::post('/instituciones', [InstitutionController::class, 'store'])->name('institutions.store');
            Route::get('/instituciones/{institution}', [InstitutionController::class, 'show'])->name('institutions.show');
            Route::get('/instituciones/{institution}/editar', [InstitutionController::class, 'edit'])->name('institutions.edit');
            Route::put('/instituciones/{institution}', [InstitutionController::class, 'update'])->name('institutions.update');
            Route::patch('/instituciones/{institution}/estado', [InstitutionController::class, 'status'])->name('institutions.status');
            Route::post('/instituciones/{institution}/administradores', [InstitutionController::class, 'addAdmin'])->name('institutions.admins.store');
            Route::patch('/instituciones/{institution}/modulos/{module:key}', [InstitutionController::class, 'authorizeModule'])->name('institutions.modules');
        });
        Route::middleware('can:plataforma.modulos')->group(function () {
            Route::get('/modulos', [ModuleController::class, 'index'])->name('modules.index');
            Route::patch('/modulos/{module:key}', [ModuleController::class, 'toggle'])->name('modules.toggle');
        });
        Route::middleware('can:plataforma.catalogos')->group(function () use ($catalogRoutes) {
            Route::view('/catalogos', 'platform.catalogs')->name('catalogos.home');
            $catalogRoutes();
        });
    });

    // Administración institucional
    Route::prefix('admin')->name('admin.')->group(function () use ($catalogRoutes) {
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

        Route::middleware('can:catalogos.gestionar')->group(function () use ($catalogRoutes) {
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
            Route::middleware('module:importacion_estudiantes')->group(function () {
                Route::get('/estudiantes/importar', [StudentController::class, 'importForm'])->name('estudiantes.import');
                Route::post('/estudiantes/importar', [StudentController::class, 'import'])->name('estudiantes.import.store');
            });
            Route::get('/estudiantes/{student}/editar', [StudentController::class, 'edit'])->name('estudiantes.edit');
            Route::put('/estudiantes/{student}', [StudentController::class, 'update'])->name('estudiantes.update');
            Route::patch('/estudiantes/{student}/estado', [StudentController::class, 'toggle'])->name('estudiantes.toggle');

            $catalogRoutes();
        });

        Route::middleware('can:configuracion.gestionar')->group(function () {
            Route::get('/institucion', [InstitutionSettingsController::class, 'show'])->name('institution.show');
            Route::put('/institucion', [InstitutionSettingsController::class, 'update'])->name('institution.update');
            Route::patch('/institucion/modulos/{module:key}', [InstitutionSettingsController::class, 'toggleModule'])->name('institution.modules');
            Route::get('/qr', [QrController::class, 'show'])->name('qr.show');
            Route::get('/qr/descargar/{format}', [QrController::class, 'download'])->whereIn('format', ['png', 'svg'])->name('qr.download');
        });
    });
});
