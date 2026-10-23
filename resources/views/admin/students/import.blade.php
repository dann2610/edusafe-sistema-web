<x-layouts.app title="Importar estudiantes" heading="Importar estudiantes desde CSV"
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Estudiantes' => route('admin.estudiantes.index'), 'Importar' => null]">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-body p-4">
                @if (! $current)
                    <p class="text-danger mb-0">Configure un periodo lectivo vigente antes de importar.</p>
                @else
                    <form method="POST" action="{{ route('admin.estudiantes.import.store') }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        <x-form.input name="archivo" type="file" label="Archivo CSV" required accept=".csv,text/csv"
                                      help="Máximo 2 MB. Si una fila tiene errores, no se importa ninguna." />
                        <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1" aria-hidden="true"></i>Importar</button>
                    </form>
                @endif
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body small">
                <div class="fw-semibold mb-2">Formato</div>
                <p>Columnas separadas por punto y coma, con encabezado opcional. Si el código ya existe, se actualizan los nombres.</p>
                <pre class="bg-light border rounded p-2 mb-3">codigo;nombres;apellidos;curso_id
EST-0001;Ana María;Pérez López;3</pre>
                @if ($courses->isNotEmpty())
                    <div class="fw-semibold mb-1">Identificadores de curso ({{ $current->name }})</div>
                    <ul class="mb-0">
                        @foreach ($courses as $c)<li><strong>{{ $c->id }}</strong> — {{ $c->display_name }}</li>@endforeach
                    </ul>
                @endif
            </div></div>
        </div>
    </div>
</x-layouts.app>
