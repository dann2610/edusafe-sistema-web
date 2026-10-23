<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Incident;
use App\Models\Referral;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Panel de inicio: accesos y contadores según los permisos de cada rol. */
class PanelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('role.permissions', 'externalEntity');

        $functions = collect(PermissionCatalog::MATRIX)
            ->filter(fn ($def, $slug) => $user->hasPermission($slug))
            ->map(fn ($def) => $def[0])
            ->values();

        $cards = [];
        if ($user->can('casos.bandeja')) {
            $cards[] = ['Casos nuevos', Incident::where('status', 'registrado')->count(), route('casos.inbox', ['estado' => 'registrado']), '#6c757d'];
            $cards[] = ['Urgentes abiertos', Incident::open()->where('priority', 'urgente')->count(), route('casos.inbox', ['prioridad' => 'urgente']), '#b42318'];
            $cards[] = ['Casos abiertos', Incident::open()->count(), route('casos.inbox'), 'var(--tic-teal)'];
        }
        if ($user->can('seguimientos.registrar')) {
            $cards[] = ['Asignados a mí', Incident::open()->whereHas('activeAssignments', fn ($q) => $q->where('user_id', $user->id))->count(), route('casos.assigned'), 'var(--tic-navy)'];
            $cards[] = ['Seguimientos vencidos o de hoy', FollowUp::where('user_id', $user->id)->whereDate('next_follow_up_on', '<=', today())
                ->whereDoesntHave('corrections')->whereHas('incident', fn ($q) => $q->open())->count(), route('casos.assigned'), '#c98a04'];
        }
        if ($user->can('incidentes.ver_propios') && ! $user->can('casos.bandeja')) {
            $cards[] = ['Mis reportes', Incident::where('reporter_user_id', $user->id)->count(), route('reportes.mine'), 'var(--tic-teal)'];
        }
        if ($user->can('derivaciones.ver_entidad')) {
            $cards[] = ['Derivaciones recibidas', Referral::where('external_entity_id', $user->external_entity_id ?? 0)->count(), route('entity.referrals.index'), '#212529'];
        }
        if ($user->can('dashboard.ver') && ! $user->can('casos.bandeja')) {
            $cards[] = ['Casos abiertos (institución)', Incident::open()->count(), route('dashboard'), 'var(--tic-teal)'];
        }
        if ($user->can('usuarios.gestionar')) {
            $cards[] = ['Cuentas activas', User::active()->count(), route('admin.usuarios.index', ['estado' => 'activos']), 'var(--tic-teal)'];
            $cards[] = ['Con contraseña temporal', User::active()->where('must_change_password', true)->count(), route('admin.usuarios.index'), '#c98a04'];
        }

        $quick = array_values(array_filter([
            $user->can('incidentes.registrar') ? ['Registrar incidente', 'bi-plus-circle', route('incidentes.create'), 'btn-primary'] : null,
            $user->can('casos.bandeja') ? ['Bandeja de casos', 'bi-inboxes', route('casos.inbox'), 'btn-outline-primary'] : null,
            $user->can('dashboard.ver') ? ['Dashboard', 'bi-bar-chart-line', route('dashboard'), 'btn-outline-primary'] : null,
            $user->can('usuarios.gestionar') ? ['Gestionar usuarios', 'bi-people', route('admin.usuarios.index'), 'btn-primary'] : null,
            $user->can('catalogos.gestionar') ? ['Catálogos', 'bi-collection', route('admin.catalogos.home'), 'btn-outline-primary'] : null,
            $user->can('derivaciones.ver_entidad') ? ['Ver derivaciones', 'bi-send-check', route('entity.referrals.index'), 'btn-primary'] : null,
        ]));

        return view('panel.index', compact('user', 'functions', 'cards', 'quick'));
    }
}
