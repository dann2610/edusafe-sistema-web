<x-layouts.public>
    <section class="public-hero" aria-labelledby="hero-title">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <p class="eyebrow mb-2">{{ $institution->name }}</p>
                    <h1 id="hero-title" class="mb-3">Si algo te preocupa en la escuela, cuéntalo. Aquí te escuchamos.</h1>
                    <p class="lead text-secondary mb-4">
                        Puedes informar sobre acoso, violencia, amenazas, ciberacoso, discriminación u otra situación que afecte a
                        alguien de la comunidad educativa. Tu reporte llega al Departamento de Consejería Estudiantil (DECE), que lo
                        revisa y da seguimiento según los protocolos de la institución.
                    </p>
                    @if ($publicReports)
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ $institution->publicRoute('report') }}" class="btn btn-accent btn-lg"><i class="bi bi-chat-square-text" aria-hidden="true"></i>Reportar un incidente</a>
                            @if ($statusLookup)
                                <a href="{{ $institution->publicRoute('status') }}" class="btn btn-outline-primary btn-lg"><i class="bi bi-search" aria-hidden="true"></i>Consultar mi reporte</a>
                            @endif
                        </div>
                        <p class="small text-secondary mt-3 mb-0"><i class="bi bi-clock me-1" aria-hidden="true"></i>Toma unos 5 minutos. Puedes escribir solo lo que sepas.</p>
                    @else
                        <div class="note" role="status"><i class="bi bi-info-circle" aria-hidden="true"></i>
                            <div>El reporte en línea no está habilitado en este momento. Acércate al DECE, a tu tutor o a un docente de confianza.</div>
                        </div>
                    @endif
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    {{-- Ilustración original: escudo y conversación, sin personas ni imágenes de violencia --}}
                    <svg class="hero-illustration" viewBox="0 0 420 340" role="img" aria-label="Ilustración: un escudo de protección junto a dos globos de conversación">
                        <rect x="20" y="40" width="380" height="270" rx="28" fill="#e8edf5"/>
                        <circle cx="330" cy="86" r="46" fill="#e4f2f3"/>
                        <path d="M190 70 L260 96 V160 C260 210 230 240 190 258 C150 240 120 210 120 160 V96 Z" fill="#13294b"/>
                        <path d="M190 92 L240 111 V160 C240 197 219 220 190 235 Z" fill="#1f3d6b"/>
                        <path d="M160 160 L182 182 L224 136" fill="none" stroke="#fff" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="262" y="176" width="118" height="64" rx="16" fill="#fff"/>
                        <path d="M286 240 L280 262 L304 240 Z" fill="#fff"/>
                        <rect x="280" y="194" width="74" height="9" rx="4.5" fill="#0d6f78"/>
                        <rect x="280" y="213" width="50" height="9" rx="4.5" fill="#cdd3da"/>
                        <rect x="40" y="200" width="96" height="54" rx="15" fill="#0d6f78"/>
                        <path d="M112 254 L120 274 L96 254 Z" fill="#0d6f78"/>
                        <rect x="56" y="217" width="56" height="8" rx="4" fill="#fff" opacity=".9"/>
                        <rect x="56" y="233" width="36" height="8" rx="4" fill="#fff" opacity=".6"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5" aria-labelledby="como-funciona">
        <h2 id="como-funciona" class="mb-1">Cómo funciona</h2>
        <p class="text-secondary mb-4">Cuatro pasos, de principio a fin.</p>
        <ol class="row g-3 list-unstyled mb-0">
            <li class="col-sm-6 col-lg-3"><div class="step-tile"><span class="step-num">1</span><h3>Cuenta qué pasó</h3><p class="text-secondary mb-0">Elige el tipo de situación y describe qué ocurrió, dónde y cuándo, aunque sea aproximado.</p></div></li>
            <li class="col-sm-6 col-lg-3"><div class="step-tile"><span class="step-num">2</span><h3>Indica quiénes participaron</h3><p class="text-secondary mb-0">Si quieres, describe a las personas afectadas o testigos. No es obligatorio dar nombres.</p></div></li>
            <li class="col-sm-6 col-lg-3"><div class="step-tile"><span class="step-num">3</span><h3>Revisa y envía</h3><p class="text-secondary mb-0">Antes de enviar verás un resumen. Al final recibes un código para identificar tu reporte.</p></div></li>
            <li class="col-sm-6 col-lg-3"><div class="step-tile"><span class="step-num">4</span><h3>El DECE da seguimiento</h3><p class="text-secondary mb-0">El equipo revisa la información en horario institucional y actúa según los protocolos.</p></div></li>
        </ol>
    </section>

    <section class="container pb-5" aria-labelledby="privacidad-titulo">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body p-4">
                    <div class="info-tile">
                        <span class="icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                        <div>
                            <h2 id="privacidad-titulo" class="h3 mb-2">Tu información se trata con reserva</h2>
                            <p class="text-secondary">Solo el personal autorizado de la institución puede ver tu reporte. No se publica ni se comparte con el resto de la comunidad.</p>
                            <p class="text-secondary mb-2">
                                @if ($institution->allow_reports_without_contact)
                                    Puedes reportar sin dejar datos de contacto. Aun así, el canal <strong>no es anónimo por completo</strong>: registra datos técnicos de seguridad, como la dirección IP, para evitar abusos.
                                @else
                                    Esta institución pide un dato de contacto para poder darte seguimiento. El canal registra datos técnicos de seguridad, como la dirección IP, para evitar abusos.
                                @endif
                            </p>
                            <a href="{{ $institution->publicRoute('privacy') }}">Leer el aviso de privacidad</a>
                        </div>
                    </div>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 border-danger-subtle"><div class="card-body p-4">
                    <div class="info-tile">
                        <span class="icon" style="background:var(--c-danger-soft);color:var(--c-danger)" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></span>
                        <div>
                            <h2 class="h3 mb-2">Si hay peligro inmediato</h2>
                            <p class="text-secondary">Este formulario no es un servicio de emergencias. Si alguien corre peligro en este momento:</p>
                            <ul class="mb-0 d-grid gap-1">
                                <li>Llama al <a href="tel:911" class="fw-semibold">ECU 911</a>.</li>
                                <li>Avisa de inmediato a un docente, a una autoridad o al DECE.</li>
                                <li>Aléjate del lugar si puedes hacerlo de forma segura.</li>
                            </ul>
                        </div>
                    </div>
                </div></div>
            </div>
        </div>
    </section>

    <section class="container pb-4" aria-labelledby="prevencion">
        <h2 id="prevencion" class="mb-1">Prevenir es tarea de todos</h2>
        <p class="text-secondary mb-4">Pequeñas acciones que hacen la diferencia.</p>
        <div class="row g-3">
            <div class="col-md-4"><div class="step-tile"><i class="bi bi-ear text-primary fs-4 d-block mb-2" aria-hidden="true"></i><h3>Escucha y acompaña</h3><p class="text-secondary mb-0">Si alguien te cuenta algo difícil, créele, no lo juzgues y ayúdale a buscar a un adulto de confianza.</p></div></div>
            <div class="col-md-4"><div class="step-tile"><i class="bi bi-phone text-primary fs-4 d-block mb-2" aria-hidden="true"></i><h3>Cuida lo que compartes</h3><p class="text-secondary mb-0">No reenvíes fotos, videos ni mensajes que humillen a otra persona. Guárdalos como evidencia y repórtalos.</p></div></div>
            <div class="col-md-4"><div class="step-tile"><i class="bi bi-people text-primary fs-4 d-block mb-2" aria-hidden="true"></i><h3>No te quedes en silencio</h3><p class="text-secondary mb-0">Reportar no es acusar: es pedir ayuda para que la institución pueda proteger a quien lo necesita.</p></div></div>
        </div>
        @if ($otherInstitutions)
            <p class="mt-4 mb-0 small"><a href="{{ route('public.directory') }}">¿Buscas otra institución? Ver el listado</a></p>
        @endif
    </section>
</x-layouts.public>
