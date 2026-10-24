<?php

namespace App\Http\Controllers;

use App\Enums\InstitutionStatus;
use App\Models\FollowUp;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel de inicio. Los indicadores se agrupan por lo que cada rol necesita
 * y separan, para el docente, los reportes que creó de los que tiene asignados.
 * Todas las consultas quedan limitadas a la institución del usuario por el
 * alcance global del modelo.
 */
class PanelController extends Controller
{
    public function __invoke(Request $request, ModuleService $modules): View
    {
        $user = $request->user()->loadMissing('role.permissions', 'externalEntity', 'institution');
        $groups = [];

        // Superadministrador: solo agregados de la plataforma
        if ($user->can('plataforma.estadisticas')) {
            $byStatus = Institution::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
            $groups['Plataforma'] = [
                ['Instituciones activas', (int) ($byStatus[InstitutionStatus::Activa->value] ?? 0), route('platform.institutions.index', ['estado' => 'activa']), 'var(--c-success)'],
                ['En preparación', (int) ($byStatus[InstitutionStatus::EnPreparacion->value] ?? 0), route('platform.institutions.index', ['estado' => 'en_preparacion']), 'var(--c-info)'],
                ['Suspendidas', (int) ($byStatus[InstitutionStatus::Suspendida->value] ?? 0), route('platform.institutions.index', ['estado' => 'suspendida']), '#c98a04'],
                ['Casos abiertos (todas)', Incident::withoutInstitutionScope()->open()->count(), route('platform.dashboard'), 'var(--c-ink)'],
            ];
        }

        // DECE: gestión de la bandeja
        if ($user->can('casos.bandeja')) {
            $groups['Bandeja de casos'] = [
                ['Nuevos sin revisar', Incident::where('status', 'registrado')->count(), route('casos.inbox', ['estado' => 'registrado']), 'var(--c-subtle)'],
                ['Urgentes abiertos', Incident::open()->where('priority', 'urgente')->count(), route('casos.inbox', ['prioridad' => 'urgente']), 'var(--c-danger)'],
                ['Requieren información', Incident::where('status', 'requiere_informacion')->count(), route('casos.inbox', ['estado' => 'requiere_informacion']), '#c98a04'],
                ['Abiertos sin responsable', Incident::open()->whereDoesntHave('activeAssignments')->count(), route('casos.inbox'), 'var(--c-info)'],
                ['En seguimiento', Incident::whereIn('status', ['asignado', 'en_seguimiento'])->count(), route('casos.inbox', ['estado' => 'en_seguimiento']), 'var(--c-teal)'],
                ['Derivados', Incident::where('status', 'derivado')->count(), route('casos.inbox', ['estado' => 'derivado']), 'var(--c-ink)'],
            ];
        }

        // Docente u otro responsable: lo que tiene asignado
        if ($user->can('seguimientos.registrar')) {
            $mine = fn (): Builder => Incident::whereHas('activeAssignments', fn ($q) => $q->where('user_id', $user->id));
            $due = FollowUp::where('user_id', $user->id)->whereDate('next_follow_up_on', '<=', today())
                ->whereDoesntHave('corrections')->whereHas('incident', fn ($q) => $q->open());
            $groups['Casos asignados a mí'] = [
                ['Asignados abiertos', $mine()->open()->count(), route('casos.assigned'), 'var(--c-ink)'],
                ['Requieren mi actuación', $mine()->open()->where(fn ($q) => $q
                    ->whereDoesntHave('followUps', fn ($f) => $f->where('user_id', $user->id))
                    ->orWhereHas('followUps', fn ($f) => $f->where('user_id', $user->id)->whereDate('next_follow_up_on', '<=', today())->whereDoesntHave('corrections')))
                    ->count(), route('casos.assigned'), 'var(--c-danger)'],
                ['Seguimientos vencidos o de hoy', $due->count(), route('casos.assigned'), '#c98a04'],
            ];
            if ($modules->enabled('derivaciones')) {
                $groups['Casos asignados a mí'][] = ['Asignados y derivados', $mine()->where('status', 'derivado')->count(), route('casos.assigned'), 'var(--c-subtle)'];
            }
        }

        // Reportes que el usuario creó (separados de los asignados)
        if ($user->can('incidentes.ver_propios') && ! $user->can('casos.bandeja')) {
            $own = fn (): Builder => Incident::where('reporter_user_id', $user->id);
            $groups['Reportes que registré'] = [
                ['Total registrados', $own()->count(), route('reportes.mine'), 'var(--c-teal)'],
                ['Pendientes de revisión', $own()->whereIn('status', ['registrado', 'en_revision'])->count(), route('reportes.mine'), 'var(--c-subtle)'],
                ['El DECE pide información', $own()->where('status', 'requiere_informacion')->count(), route('reportes.mine'), 'var(--c-danger)'],
                ['En atención', $own()->whereIn('status', ['asignado', 'en_seguimiento', 'derivado'])->count(), route('reportes.mine'), 'var(--c-info)'],
                ['Atendidos (cerrados)', $own()->where('status', 'cerrado')->count(), route('reportes.mine'), 'var(--c-success)'],
            ];
        }

        if ($user->can('derivaciones.ver_entidad') && $modules->enabled('derivaciones')) {
            $groups['Derivaciones'] = [
                ['Derivaciones recibidas', Referral::where('external_entity_id', $user->external_entity_id ?? 0)->count(), route('entity.referrals.index'), 'var(--c-ink)'],
            ];
        }
        if ($user->can('dashboard.ver') && ! $user->can('casos.bandeja') && $modules->enabled('dashboard')) {
            $groups['Institución'] = [
                ['Casos abiertos', Incident::open()->count(), route('dashboard'), 'var(--c-teal)'],
                ['Cerrados en los últimos 30 días', Incident::where('status', 'cerrado')->where('closed_at', '>=', now()->subDays(30))->count(), route('dashboard'), 'var(--c-success)'],
            ];
        }
        if ($user->can('usuarios.gestionar')) {
            $groups['Administración'] = [
                ['Cuentas activas', User::active()->ofInstitution($user->institution_id)->count(), route('admin.usuarios.index', ['estado' => 'activos']), 'var(--c-teal)'],
                ['Con contraseña temporal', User::active()->ofInstitution($user->institution_id)->where('must_change_password', true)->count(), route('admin.usuarios.index'), '#c98a04'],
                ['Categorías propias', IncidentCategory::where('institution_id', $user->institution_id)->where('is_active', true)->count(), route('admin.categories.index'), 'var(--c-ink)'],
            ];
        }

        $unread = $user->unreadNotifications()->count();

        $quick = array_values(array_filter([
            $user->can('incidentes.registrar') ? ['Registrar incidente', 'bi-plus-circle', route('incidentes.create'), 'btn-primary'] : null,
            $user->can('casos.bandeja') ? ['Bandeja de casos', 'bi-inboxes', route('casos.inbox'), 'btn-outline-primary'] : null,
            $user->can('plataforma.instituciones') ? ['Registrar institución', 'bi-plus-lg', route('platform.institutions.create'), 'btn-primary'] : null,
            $user->can('usuarios.gestionar') ? ['Gestionar usuarios', 'bi-people', route('admin.usuarios.index'), 'btn-outline-primary'] : null,
            $user->can('derivaciones.ver_entidad') && $modules->enabled('derivaciones') ? ['Ver derivaciones', 'bi-send-check', route('entity.referrals.index'), 'btn-primary'] : null,
        ]));

        $recent = $user->notifications()->latest()->limit(5)->get();

        return view('panel.index', compact('user', 'groups', 'quick', 'unread', 'recent'));
    }
}
