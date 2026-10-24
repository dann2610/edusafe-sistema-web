<?php

namespace App\Http\Controllers;

use App\Enums\AuditResult;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Consulta de la bitácora (solo lectura: los registros son inmutables). */
class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $f = $request->validate([
            'accion' => ['nullable', 'string', 'max:60'],
            'usuario' => ['nullable', 'integer'],
            'resultado' => ['nullable', Rule::enum(AuditResult::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $logs = AuditLog::with('user')
            ->when($f['accion'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($f['usuario'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['resultado'] ?? null, fn ($q, $v) => $q->where('result', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'filters' => $f,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action')->mapWithKeys(fn ($a) => [$a => str_replace('_', ' ', ucfirst($a))])->all(),
            'users' => User::when(auth()->user()->institution_id, fn ($q, $id) => $q->where('institution_id', $id))->orderBy('name')->pluck('name', 'id')->all(),
            'results' => AuditResult::options(),
        ]);
    }
}
