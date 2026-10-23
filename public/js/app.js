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

    // Enfoca el primer campo con error para lectores de pantalla y teclado
    document.addEventListener('DOMContentLoaded', function () {
        var invalid = document.querySelector('.is-invalid');
        if (invalid) invalid.focus();
    });
})();
