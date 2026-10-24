<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Notifications\FollowUpDueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Notifica a cada responsable los seguimientos con fecha prevista hoy o vencida
 * en casos abiertos. No duplica avisos: cada seguimiento se notifica una sola vez.
 * Programado a diario en routes/console.php (requiere "php artisan schedule:work"
 * o una tarea programada del sistema operativo).
 */
class SendFollowUpRemindersCommand extends Command
{
    protected $signature = 'tic:recordatorios-seguimiento';

    protected $description = 'Envía notificaciones internas de seguimientos pendientes.';

    public function handle(): int
    {
        $alreadyNotified = DB::table('notifications')
            ->where('type', FollowUpDueNotification::class)
            ->pluck('data')
            ->map(fn ($d) => json_decode($d, true)['follow_up_id'] ?? null)
            ->filter()->all();

        $due = FollowUp::with(['incident', 'user'])
            ->whereNotNull('next_follow_up_on')
            ->whereDate('next_follow_up_on', '<=', today())
            ->whereNotIn('id', $alreadyNotified ?: [0])
            ->whereDoesntHave('corrections')
            ->whereHas('incident', fn ($q) => $q->open())
            ->get();

        $modules = app(\App\Services\ModuleService::class);
        $sent = 0;
        foreach ($due as $followUp) {
            if (! $modules->enabled('recordatorios', $followUp->incident->institution_id)) {
                continue; // módulo desactivado para esa institución
            }
            // Si ya existe un seguimiento posterior, el pendiente se considera atendido
            $newer = FollowUp::where('incident_id', $followUp->incident_id)->where('performed_at', '>', $followUp->performed_at)->exists();
            if ($newer || ! $followUp->user?->is_active) {
                continue;
            }
            $followUp->user->notify(new FollowUpDueNotification($followUp));
            $sent++;
        }

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }
}
