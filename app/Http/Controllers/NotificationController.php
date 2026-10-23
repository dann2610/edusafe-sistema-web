<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notificaciones internas. Solo se marcan como leídas por una acción
 * explícita del usuario (abrir el aviso o marcarlo).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /** Abre el enlace del aviso y lo marca como leído (acción explícita). */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('panel');
        // Solo se permiten redirecciones dentro del propio sistema
        if (! str_starts_with($url, url('/'))) {
            $url = route('panel');
        }

        return redirect()->to($url);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Todas las notificaciones quedaron como leídas.');
    }
}
