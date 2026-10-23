<x-layouts.app title="Notificaciones" heading="Notificaciones" :breadcrumbs="['Notificaciones' => null]">
    @if (auth()->user()->unreadNotifications()->exists())
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-check2-all me-1" aria-hidden="true"></i>Marcar todas como leídas</button>
            </form>
        </x-slot:actions>
    @endif
    <div class="card">
        <ul class="list-group list-group-flush">
            @forelse ($notifications as $n)
                @php
                    $icon = match ($n->data['tipo'] ?? '') { 'urgente' => 'bi-exclamation-triangle-fill text-danger', 'asignacion' => 'bi-person-check', 'estado' => 'bi-arrow-left-right', 'recordatorio' => 'bi-alarm', default => 'bi-bell' };
                @endphp
                <li @class(['list-group-item d-flex gap-3 align-items-start py-3', 'notification-unread' => ! $n->read_at])>
                    <i class="bi {{ $icon }} fs-5" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <a href="{{ route('notifications.open', $n->id) }}" class="@if (! $n->read_at) fw-semibold @endif text-body">{{ $n->data['mensaje'] ?? 'Notificación' }}</a>
                        <div class="small text-secondary">{{ $n->created_at->format('d/m/Y H:i') }} · {{ $n->created_at->diffForHumans() }}</div>
                    </div>
                    @unless ($n->read_at)
                        <span class="visually-hidden">Sin leer</span>
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-link" type="submit">Marcar leída</button>
                        </form>
                    @endunless
                </li>
            @empty
                <li class="list-group-item"><x-empty icon="bi-bell-slash" text="No tiene notificaciones." /></li>
            @endforelse
        </ul>
        @if ($notifications->hasPages())<div class="card-body border-top">{{ $notifications->links() }}</div>@endif
    </div>
</x-layouts.app>
