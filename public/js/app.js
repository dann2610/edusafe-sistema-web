/*
 * Comportamientos de interfaz. No contiene reglas de negocio: toda validación
 * y autorización se repite en el servidor. Se carga como archivo externo
 * porque la política de seguridad (CSP) bloquea los scripts en línea.
 */
(function () {
    'use strict';

    // Confirmación antes de enviar formularios críticos: <form data-confirm="¿Seguro?">
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    // Evita envíos dobles: deshabilita el botón tras el primer envío
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        var buttons = event.target.querySelectorAll('button[type="submit"]');
        buttons.forEach(function (btn) {
            window.setTimeout(function () { btn.disabled = true; }, 0);
        });
    });

    // Mostrar u ocultar contraseña: <button data-toggle-password="idDelCampo">
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-toggle-password]');
        if (!btn) return;
        var input = document.getElementById(btn.getAttribute('data-toggle-password'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        var icon = btn.querySelector('.bi');
        if (icon) {
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        }
    });

    // Copiar al portapapeles: <button data-copy="texto">
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-copy]');
        if (!btn || !navigator.clipboard) return;
        navigator.clipboard.writeText(btn.getAttribute('data-copy')).then(function () {
            var original = btn.innerHTML;
            btn.textContent = 'Copiado';
            window.setTimeout(function () { btn.innerHTML = original; }, 1800);
        });
    });

    // Muestra el campo de entidad externa solo para el rol policial
    function syncEntityField() {
        var select = document.getElementById('role_id');
        var group = document.getElementById('entity-group');
        if (!select || !group) return;
        var isPolice = select.value !== '' && select.value === select.getAttribute('data-police-role');
        group.hidden = !isPolice;
        var entity = document.getElementById('external_entity_id');
        if (entity) entity.required = isPolice;
    }
    document.addEventListener('change', function (event) {
        if (event.target.id === 'role_id') syncEntityField();
    });
    document.addEventListener('DOMContentLoaded', syncEntityField);

    // Confirmación en enlaces sensibles: <a data-confirm-link="¿Seguro?">
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-confirm-link]');
        if (link && !window.confirm(link.getAttribute('data-confirm-link'))) {
            event.preventDefault();
        }
    });

    // Filas dinámicas (involucrados): <button data-add-row="contenedorId"> + <template id="contenedorId-template">
    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add-row]');
        if (add) {
            var id = add.getAttribute('data-add-row');
            var container = document.getElementById(id);
            var template = document.getElementById(id + '-template');
            if (!container || !template) return;
            if (container.querySelectorAll('[data-row]').length >= 10) {
                window.alert('Puede registrar hasta 10 personas involucradas.');
                return;
            }
            var index = parseInt(container.getAttribute('data-next-index') || '0', 10);
            container.setAttribute('data-next-index', String(index + 1));
            var html = template.innerHTML.split('__INDEX__').join(String(index));
            container.insertAdjacentHTML('beforeend', html);
            var rows = container.querySelectorAll('[data-row]');
            var first = rows[rows.length - 1].querySelector('select, input');
            if (first) first.focus();
            return;
        }
        var remove = event.target.closest('[data-remove-row]');
        if (remove) {
            var row = remove.closest('[data-row]');
            if (row) row.remove();
        }
    });

    // Cambio de estado: el resumen de cierre solo se pide al elegir "Cerrado"
    function syncClosure() {
        var select = document.getElementById('transition-status');
        var group = document.getElementById('closure-group');
        if (!select || !group) return;
        group.hidden = select.value !== 'cerrado';
    }
    document.addEventListener('change', function (event) {
        if (event.target.id === 'transition-status') syncClosure();
    });
    document.addEventListener('DOMContentLoaded', syncClosure);

    // Pestañas: abre la que contiene errores o la indicada en la URL (#pane-...)
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof bootstrap === 'undefined') return;
        var target = null;
        var invalid = document.querySelector('.tab-pane .is-invalid');
        if (invalid) target = invalid.closest('.tab-pane');
        if (!target && window.location.hash.indexOf('#pane-') === 0) target = document.querySelector(window.location.hash);
        if (target) {
            var trigger = document.querySelector('[data-bs-target="#' + target.id + '"]');
            if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                history.replaceState(null, '', tab.getAttribute('data-bs-target'));
            });
        });
    });

    // Enfoca el primer campo con error para lectores de pantalla y teclado
    document.addEventListener('DOMContentLoaded', function () {
        var invalid = document.querySelector('.is-invalid');
        if (invalid) invalid.focus();
    });
})();
