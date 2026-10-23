<x-layouts.app title="Casos asignados" heading="Casos asignados a mí"
                lead="Casos en los que usted es responsable activo. Registre aquí sus actuaciones de seguimiento."
                :breadcrumbs="['Casos asignados' => null]">
    <div class="card">
        @include('cases._table', ['cases' => $cases])
    </div>
</x-layouts.app>
