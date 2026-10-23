<x-layouts.public title="Aviso de privacidad">
    <div class="container py-4" style="max-width:820px">
        <h1 class="h3 fw-bold mb-3" style="color:var(--tic-navy)">Aviso de privacidad y tratamiento de datos</h1>
        <div class="alert alert-secondary small" role="note">
            <strong>Texto base pendiente de aprobación institucional.</strong> La Unidad Educativa debe revisarlo y completarlo
            con su responsable de tratamiento de datos antes de usar el sistema con información real.
        </div>
        <div class="card"><div class="card-body p-4">
            <h2 class="h6 fw-bold">Responsable</h2>
            <p>{{ config('seguridad_escolar.institucion.nombre') }}, a través de su Departamento de Consejería Estudiantil (DECE) y las autoridades institucionales.</p>

            <h2 class="h6 fw-bold">Finalidad</h2>
            <p>Registrar, atender y dar seguimiento a situaciones que afecten la seguridad y la convivencia escolar, y elaborar estadísticas agregadas para la prevención. Los datos no se usan para otros fines.</p>

            <h2 class="h6 fw-bold">Datos que se tratan</h2>
            <ul>
                <li>La descripción de la situación, la fecha, el lugar y la categoría.</li>
                <li>Opcionalmente, tu nombre y un medio de contacto.</li>
                <li>Datos técnicos de seguridad (por ejemplo, la dirección IP), para prevenir abusos del formulario.</li>
                <li>En el registro interno, datos mínimos de estudiantes involucrados: código institucional, nombres y curso.</li>
            </ul>

            <h2 class="h6 fw-bold">Quién accede</h2>
            <p>Solo el personal autorizado del DECE y los responsables designados para cada caso. Las autoridades consultan estadísticas sin datos personales. Cuando un protocolo lo exige, el caso se deriva a entidades competentes, como la Policía Nacional, la Fiscalía o la Junta Cantonal de Protección de Derechos, compartiendo solo la información necesaria y por los canales oficiales.</p>

            <h2 class="h6 fw-bold">Limitaciones del canal</h2>
            <p>Este formulario no es anónimo ni atiende emergencias en tiempo real. Ante un peligro inmediato llama al ECU 911. El sistema no determina la veracidad de un reporte ni establece responsabilidades: esas decisiones corresponden a la institución conforme a los protocolos vigentes.</p>

            <h2 class="h6 fw-bold">Conservación y seguridad</h2>
            <p>La información se guarda con controles de acceso, contraseñas cifradas y registro de auditoría. El plazo de conservación será el que defina la institución conforme a la normativa aplicable.</p>

            <h2 class="h6 fw-bold mb-1">Tus derechos</h2>
            <p class="mb-0">Puedes ejercer los derechos reconocidos por la Ley Orgánica de Protección de Datos Personales del Ecuador acercándote a la institución.</p>
        </div></div>
        <a href="{{ route('public.home') }}" class="btn btn-outline-primary mt-3">Volver al inicio</a>
    </div>
</x-layouts.public>
