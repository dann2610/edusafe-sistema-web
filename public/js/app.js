/*
 * Comportamientos de interfaz. No contiene reglas de negocio: toda validación
 * y autorización se repite en el servidor. Se carga como archivo externo
 * porque la política de seguridad (CSP) bloquea los scripts en línea.
 * Todo texto proveniente de la página se inserta con textContent (nunca innerHTML).
 */
(function () {
    'use strict';

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
    function onReady(fn) { if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

    /* ------------------------------------------------ Diálogo de confirmación
     * <form data-confirm="Mensaje" data-confirm-title="Título" data-confirm-ok="Texto botón" data-confirm-tone="danger">
     * <a data-confirm-link="Mensaje">. Sin Bootstrap disponible, recurre a window.confirm.
     */
    var dialog = null;
    function buildDialog() {
        if (dialog) return dialog;
        var el = document.createElement('div');
        el.className = 'modal fade';
        el.id = 'confirm-dialog';
        el.tabIndex = -1;
        el.setAttribute('aria-hidden', 'true');
        el.setAttribute('aria-labelledby', 'confirm-dialog-title');
        el.innerHTML =
            '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
            '<div class="modal-header border-0 pb-0"><h2 class="modal-title h3" id="confirm-dialog-title"></h2>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>' +
            '<div class="modal-body pt-2"><p class="mb-0 text-secondary" data-confirm-message></p></div>' +
            '<div class="modal-footer border-0 pt-0">' +
            '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>' +
            '<button type="button" class="btn" data-confirm-accept></button></div></div></div>';
        document.body.appendChild(el);
        dialog = { el: el, modal: window.bootstrap.Modal.getOrCreateInstance(el) };
        return dialog;
    }
    function askConfirm(opts, onAccept) {
        if (!window.bootstrap || !window.bootstrap.Modal) {
            if (window.confirm(opts.message)) onAccept();
            return;
        }
        var d = buildDialog();
        $('#confirm-dialog-title', d.el).textContent = opts.title || 'Confirmar acción';
        $('[data-confirm-message]', d.el).textContent = opts.message;
        var accept = $('[data-confirm-accept]', d.el);
        accept.textContent = opts.ok || 'Confirmar';
        accept.className = 'btn ' + (opts.tone === 'danger' ? 'btn-danger' : 'btn-primary');
        var fresh = accept.cloneNode(true);
        accept.parentNode.replaceChild(fresh, accept);
        fresh.addEventListener('click', function () { d.modal.hide(); onAccept(); });
        d.el.addEventListener('shown.bs.modal', function () { fresh.focus(); }, { once: true });
        d.modal.show();
    }
    function optsFrom(el, attr) {
        return {
            message: el.getAttribute(attr),
            title: el.getAttribute('data-confirm-title'),
            ok: el.getAttribute('data-confirm-ok'),
            tone: el.getAttribute('data-confirm-tone')
        };
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.hasAttribute('data-confirm') || form.__confirmed) return;
        event.preventDefault();
        askConfirm(optsFrom(form, 'data-confirm'), function () {
            form.__confirmed = true;
            if (form.requestSubmit) form.requestSubmit(); else form.submit();
        });
    }, true);

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-confirm-link]');
        if (!link) return;
        event.preventDefault();
        askConfirm(optsFrom(link, 'data-confirm-link'), function () { window.location.href = link.href; });
    });

    // Evita envíos dobles: deshabilita el botón tras el primer envío confirmado
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        $$('button[type="submit"]', event.target).forEach(function (btn) {
            window.setTimeout(function () { btn.disabled = true; }, 0);
        });
    });

    /* ------------------------------------------------ Utilidades de formulario */
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
        if (icon) { icon.classList.toggle('bi-eye', !show); icon.classList.toggle('bi-eye-slash', show); }
    });

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-copy]');
        if (!btn || !navigator.clipboard) return;
        navigator.clipboard.writeText(btn.getAttribute('data-copy')).then(function () {
            var original = btn.innerHTML;
            btn.textContent = 'Copiado';
            window.setTimeout(function () { btn.innerHTML = original; }, 1800);
        });
    });

    // Campo de entidad externa solo para el rol policial
    function syncEntityField() {
        var select = document.getElementById('role_id');
        var group = document.getElementById('entity-group');
        if (!select || !group) return;
        var isPolice = select.value !== '' && select.value === select.getAttribute('data-police-role');
        group.hidden = !isPolice;
        var entity = document.getElementById('external_entity_id');
        if (entity) entity.required = isPolice;
    }

    // Cambio de estado: el resumen de cierre solo se pide al elegir "Cerrado"
    function syncClosure() {
        var select = document.getElementById('transition-status');
        var group = document.getElementById('closure-group');
        if (select && group) group.hidden = select.value !== 'cerrado';
    }

    // <input data-reveals="idContenedor" data-reveal-value="valor">: muestra el contenedor si el radio con ese valor está marcado
    function syncReveals() {
        var groups = {};
        $$('[data-reveals]').forEach(function (input) { groups[input.getAttribute('data-reveals')] = input; });
        Object.keys(groups).forEach(function (id) {
            var target = document.getElementById(id);
            var input = groups[id];
            if (!target) return;
            var wanted = input.getAttribute('data-reveal-value');
            var checked = $$('input[name="' + input.name + '"]:checked')[0];
            target.hidden = !(checked && checked.value === wanted);
        });
    }

    // <input type=checkbox data-disables="id1,id2">: deshabilita esos campos cuando está marcado
    function syncDisables() {
        $$('[data-disables]').forEach(function (box) {
            box.getAttribute('data-disables').split(',').forEach(function (id) {
                var el = document.getElementById(id.trim());
                if (!el) return;
                var byOther = $$('[data-disables]').some(function (o) {
                    return o !== box && o.checked && !o.disabled && o.getAttribute('data-disables').split(',').indexOf(id) !== -1;
                });
                el.disabled = box.checked || byOther;
            });
        });
    }

    // Campos adicionales: solo se habilitan los de la categoría elegida
    function syncCategoryFields() {
        var chosen = $$('[data-category-choice]:checked')[0] || $$('select[data-category-select]')[0];
        $$('[data-category-fields]').forEach(function (box) {
            var active = chosen && box.getAttribute('data-category-fields') === chosen.value;
            box.hidden = !active;
            $$('input, select, textarea', box).forEach(function (el) {
                el.disabled = !active;
                if (el.hasAttribute('data-required')) el.required = !!active;
            });
        });
    }

    document.addEventListener('change', function (event) {
        var t = event.target;
        if (t.id === 'role_id') syncEntityField();
        if (t.id === 'transition-status') syncClosure();
        if (t.hasAttribute('data-reveals') || t.type === 'radio') syncReveals();
        if (t.hasAttribute('data-disables')) syncDisables();
        if (t.hasAttribute('data-category-choice') || t.hasAttribute('data-category-select')) syncCategoryFields();
    });

    // Filas dinámicas: <button data-add-row="contenedorId"> + <template id="contenedorId-template">
    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add-row]');
        if (add) {
            var id = add.getAttribute('data-add-row');
            var container = document.getElementById(id);
            var template = document.getElementById(id + '-template');
            if (!container || !template) return;
            var max = parseInt(container.getAttribute('data-max-rows') || '10', 10);
            if (container.querySelectorAll('[data-row]').length >= max) {
                add.disabled = true;
                return;
            }
            var index = parseInt(container.getAttribute('data-next-index') || '0', 10);
            container.setAttribute('data-next-index', String(index + 1));
            container.insertAdjacentHTML('beforeend', template.innerHTML.split('__INDEX__').join(String(index)));
            var rows = container.querySelectorAll('[data-row]');
            var first = rows[rows.length - 1].querySelector('select, input');
            if (first) first.focus();
            add.disabled = rows.length >= max;
            return;
        }
        var remove = event.target.closest('[data-remove-row]');
        if (remove) {
            var row = remove.closest('[data-row]');
            var parent = row && row.parentNode;
            if (row) row.remove();
            var btn = parent && parent.id ? $('[data-add-row="' + parent.id + '"]') : null;
            if (btn) btn.disabled = false;
        }
    });

    /* ------------------------------------------------ Formulario por pasos
     * Mejora progresiva: sin JavaScript se ve el formulario completo y se envía
     * igual. El servidor vuelve a validar todo.
     */
    // Oculto por la lógica del formulario (no por estar en otro paso del asistente)
    function hiddenInside(el) {
        for (var n = el; n && !n.classList.contains('wizard-panel'); n = n.parentElement) {
            if (n.hidden) return true;
        }
        return false;
    }
    function isActive(el) {
        return !el.disabled && !hiddenInside(el);
    }

    function labelFor(el) {
        if (el.id) {
            var l = $('label[for="' + el.id + '"]');
            if (l) return l.textContent.replace('*', '').trim();
        }
        return el.name;
    }

    function addReview(dl, term, value) {
        if (value === null || value === undefined || String(value).trim() === '') return;
        var dt = document.createElement('dt');
        dt.textContent = term;
        var dd = document.createElement('dd');
        dd.textContent = value;
        dl.appendChild(dt);
        dl.appendChild(dd);
    }

    function buildReview(form) {
        var dl = $('#review-summary', form);
        if (!dl) return;
        while (dl.firstChild) dl.removeChild(dl.firstChild);
        $$('.wizard-panel', form).forEach(function (panel) {
            if (panel.getAttribute('data-step') === 'revision') return;
            var header = document.createElement('dt');
            header.className = 'h3 text-body mt-2 mb-2';
            header.textContent = panel.getAttribute('data-title');
            var mark = dl.childNodes.length;
            dl.appendChild(header);
            $$('[data-review-radio], [data-review], [data-review-check], [data-review-rows], [data-review-files], [data-category-fields]', panel).forEach(function (el) {
                if (hiddenInside(el) || el.disabled) return;
                if (el.hasAttribute('data-review-radio')) {
                    var c = $('input:checked', el);
                    var text = c ? $('.choice-text', c.parentNode) : null;
                    addReview(dl, el.getAttribute('data-review-radio'), text ? text.textContent.trim() : '');
                } else if (el.hasAttribute('data-review-check')) {
                    if (el.checked) addReview(dl, el.getAttribute('data-review-check'), 'Sí');
                } else if (el.hasAttribute('data-review-rows')) {
                    var lines = $$('[data-row]', el).map(function (row) {
                        var s = $('[data-row-label]', row);
                        var v = $('[data-row-value]', row);
                        if (!v || v.value.trim() === '') return null;
                        return s.options[s.selectedIndex].text + ': ' + v.value.trim();
                    }).filter(Boolean);
                    addReview(dl, el.getAttribute('data-review-rows'), lines.join('\n'));
                } else if (el.hasAttribute('data-review-files')) {
                    var names = Array.prototype.map.call(el.files || [], function (f) { return f.name; });
                    addReview(dl, el.getAttribute('data-review-files'), names.join('\n'));
                } else if (el.hasAttribute('data-category-fields')) {
                    $$('input, select, textarea', el).forEach(function (f) {
                        if (f.disabled || f.type === 'hidden') return;
                        var value = f.type === 'checkbox' ? (f.checked ? 'Sí' : 'No')
                            : (f.tagName === 'SELECT' ? (f.value ? f.options[f.selectedIndex].text : '') : f.value);
                        addReview(dl, labelFor(f), value);
                    });
                } else {
                    var val = el.tagName === 'SELECT' ? (el.value ? el.options[el.selectedIndex].text : '') : el.value;
                    if (el.type === 'date' && val) { var p = val.split('-'); val = p[2] + '/' + p[1] + '/' + p[0]; }
                    addReview(dl, el.getAttribute('data-review'), val);
                }
            });
            if (dl.childNodes.length === mark + 1) addReview(dl, 'Sin datos', 'No se completó información en este paso.');
        });
    }

    function initWizard(form) {
        var panels = $$('.wizard-panel', form);
        if (panels.length < 2) return;
        form.classList.add('js-wizard');
        var prev = $('[data-wizard-prev]', form);
        var next = $('[data-wizard-next]', form);
        var submit = $('[data-wizard-submit]', form);
        var progress = $('#wizard-progress', form);
        var current = 0;

        function show(i, focus) {
            current = Math.max(0, Math.min(panels.length - 1, i));
            panels.forEach(function (p, n) { p.hidden = n !== current; });
            $$('[data-step-indicator]', form).forEach(function (li, n) {
                li.classList.toggle('is-done', n < current);
                li.classList.toggle('is-current', n === current);
            });
            if (progress) progress.textContent = 'Paso ' + (current + 1) + ' de ' + panels.length + ': ' + panels[current].getAttribute('data-title');
            prev.hidden = current === 0;
            var last = current === panels.length - 1;
            next.hidden = last;
            submit.hidden = !last;
            if (last) buildReview(form);
            if (focus) {
                var h = $('h2', panels[current]);
                if (h) { h.tabIndex = -1; h.focus(); }
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Validación del paso actual con las reglas HTML; el servidor valida todo de nuevo
        function validate(panel) {
            var firstBad = null;
            $$('input, select, textarea', panel).forEach(function (el) {
                if (!isActive(el) || el.type === 'hidden' || !el.willValidate) return;
                var ok = el.checkValidity();
                if (el.type === 'radio') {
                    var group = $$('input[name="' + el.name + '"]', panel);
                    ok = !el.required || group.some(function (r) { return r.checked; });
                }
                if (el.type === 'file' && el.files && el.hasAttribute('data-max-files')) {
                    ok = ok && el.files.length <= parseInt(el.getAttribute('data-max-files'), 10);
                }
                var holder = el.type === 'radio' ? el.closest('fieldset') : el.parentNode;
                var msg = holder ? $('.js-error[data-for="' + el.name + '"]', holder) : null;
                if (!ok) {
                    el.classList.add('is-invalid');
                    el.setAttribute('aria-invalid', 'true');
                    if (holder && !msg) {
                        msg = document.createElement('div');
                        msg.className = 'invalid-feedback d-block js-error';
                        msg.setAttribute('data-for', el.name);
                        msg.textContent = el.type === 'radio' ? 'Elija una opción.'
                            : (el.validity.tooShort ? 'Escriba al menos ' + el.minLength + ' caracteres.'
                            : (el.type === 'file' ? 'Puede adjuntar hasta ' + el.getAttribute('data-max-files') + ' archivos.' : 'Complete este campo.'));
                        holder.appendChild(msg);
                    }
                    if (!firstBad) firstBad = el;
                } else {
                    if (el.type !== 'radio') { el.classList.remove('is-invalid'); el.removeAttribute('aria-invalid'); }
                    if (msg) msg.remove();
                }
            });
            if (firstBad) firstBad.focus();
            return !firstBad;
        }

        form.addEventListener('input', function (e) {
            var el = e.target;
            if (el.classList.contains('is-invalid') && el.checkValidity()) {
                el.classList.remove('is-invalid');
                var m = el.parentNode && $('.js-error[data-for="' + el.name + '"]', el.parentNode);
                if (m) m.remove();
            }
        });
        form.addEventListener('change', function (e) {
            if (e.target.type === 'radio') {
                var fs = e.target.closest('fieldset');
                var m = fs && $('.js-error[data-for="' + e.target.name + '"]', fs);
                if (m) m.remove();
            }
        });

        next.addEventListener('click', function () { if (validate(panels[current])) show(current + 1, true); });
        prev.addEventListener('click', function () { show(current - 1, true); });
        form.addEventListener('submit', function (e) {
            for (var i = 0; i < panels.length; i++) {
                if (!validate(panels[i])) { e.preventDefault(); e.stopImmediatePropagation(); show(i, false); validate(panels[i]); return; }
            }
        }, true);

        // Si el servidor devolvió errores, abre el primer paso que los contiene
        var start = 0;
        var invalid = $('.is-invalid', form) || $('.invalid-feedback.d-block', form);
        if (invalid) {
            var panel = invalid.closest('.wizard-panel');
            start = panels.indexOf(panel);
        }
        show(start < 0 ? 0 : start, false);
    }

    /* ------------------------------------------------ Pestañas del expediente */
    function initTabs() {
        if (typeof bootstrap === 'undefined') return;
        var target = null;
        var invalid = document.querySelector('.tab-pane .is-invalid');
        if (invalid) target = invalid.closest('.tab-pane');
        if (!target && window.location.hash.indexOf('#pane-') === 0) target = document.querySelector(window.location.hash);
        if (target) {
            var trigger = document.querySelector('[data-bs-target="#' + target.id + '"]');
            if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
        $$('[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () { history.replaceState(null, '', tab.getAttribute('data-bs-target')); });
        });
    }

    onReady(function () {
        syncEntityField();
        syncClosure();
        syncReveals();
        syncDisables();
        syncCategoryFields();
        $$('form[data-wizard]').forEach(initWizard);
        initTabs();
        var summary = document.getElementById('form-errors');
        var invalid = document.querySelector('.is-invalid');
        if (summary) summary.focus();
        else if (invalid && !invalid.closest('[hidden]')) invalid.focus();
    });
})();
