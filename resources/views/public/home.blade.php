<x-layouts.public>
    <section class="public-hero">
        <div class="container py-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <p class="text-uppercase small fw-semibold mb-2 hero-kicker">Convivencia escolar segura</p>
                    <h1 class="display-6 fw-bold mb-3">Aquí te escuchamos. Juntos prevenimos.</h1>
                    <p class="lead mb-4">
                        Si viviste o presenciaste acoso, violencia, amenazas, ciberacoso u otra situación que te preocupa en la
                        {{ config('seguridad_escolar.institucion.nombre') }}, cuéntanoslo. El Departamento de Consejería Estudiantil (DECE)
                        recibirá tu reporte y dará seguimiento con los protocolos de la institución.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($publicReports)
                            <a href="{{ route('public.report') }}" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-megaphone me-2" aria-hidden="true"></i>Reportar un incidente</a>
                        @endif
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg"><i class="bi bi-person-lock me-2" aria-hidden="true"></i>Iniciar sesión</a>
                    </div>
                    @unless ($publicReports)
                        <p class="mt-3 small">El reporte en línea no está habilitado. Acércate al DECE o a un docente de confianza.</p>
                    @endunless
                </div>
                <div class="col-lg-5">
                    <div class="hero-card p-4">
                        <div class="fw-semibold mb-3"><i class="bi bi-signpost-split me-2" aria-hidden="true"></i>¿Cómo reportar?</div>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Si hay <strong>peligro inmediato</strong>, llama primero al <strong>ECU 911</strong> y avisa a un adulto de la institución.</li>
                            <li class="mb-2">Pulsa <strong>«Reportar un incidente»</strong> y describe lo que pasó: qué, dónde y cuándo, aunque sea aproximado.</li>
                            <li class="mb-2">Guarda el <strong>código</strong> que recibirás al final.</li>
                            <li>El DECE revisará tu reporte. También puedes acercarte personalmente.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <h2 class="h4 fw-bold mb-4" style="color:var(--tic-navy)">Prevenir es tarea de todos</h2>
        <div class="row g-3">
            @foreach ([
                ['bi-chat-heart', 'Habla con alguien de confianza', 'Contar lo que pasa es el primer paso. Docentes, tutores y el DECE están para escucharte.'],
                ['bi-phone', 'Cuida tu vida digital', 'No compartas contraseñas, fotos ni datos personales. Guarda capturas si recibes mensajes ofensivos y no respondas con agresiones.'],
                ['bi-people', 'No seas espectador', 'Si ves que alguien sufre acoso, no lo ignores ni lo difundas. Acompaña a la persona y avisa a un adulto.'],
                ['bi-shield-check', 'Respeto y diversidad', 'Nadie debe ser excluido ni humillado por su apariencia, origen, forma de pensar o cualquier otra condición.'],
            ] as [$icon, $title, $text])
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi {{ $icon }} fs-3 mb-2 d-block" style="color:var(--tic-teal)" aria-hidden="true"></i>
                            <h3 class="h6 fw-bold">{{ $title }}</h3>
                            <p class="small text-secondary mb-0">{{ $text }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100 border-danger-subtle">
                    <div class="card-body">
                        <h2 class="h5 fw-bold text-danger"><i class="bi bi-telephone-fill me-2" aria-hidden="true"></i>Canales oficiales de emergencia</h2>
                        <ul class="mb-2">
                            @foreach (config('seguridad_escolar.emergencias') as $e)
                                <li><strong>{{ $e['nombre'] }}:</strong> {{ $e['contacto'] }}</li>
                            @endforeach
                        </ul>
                        <p class="small text-secondary mb-0">Este sistema es una herramienta institucional de registro y seguimiento. No reemplaza a los servicios de emergencia, a la Policía Nacional ni a los protocolos oficiales.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5 fw-bold" style="color:var(--tic-navy)"><i class="bi bi-lock me-2" aria-hidden="true"></i>Tus datos</h2>
                        <p class="small mb-2">La información se usa únicamente para atender la situación reportada y solo la consulta el personal autorizado de la institución.</p>
                        <p class="small mb-2">Puedes dejar tus datos de contacto para que el DECE te ayude mejor. Si prefieres no hacerlo, igual puedes reportar, pero no es un canal anónimo: el sistema registra datos técnicos de seguridad.</p>
                        <a href="{{ route('public.privacy') }}" class="small">Leer el aviso de privacidad completo</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
