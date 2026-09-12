// assets/js/onboarding-ui.js — PinturaPittsburgh_LaPazBCS
// Saludo dinámico, barra de cumplimiento y firma digital de
// Colaboradores/onboarding_colaborador.html. Antes vivía inline en el HTML
// (violaba la propia Regla de Oro que el documento le exige a Rafael) —
// migrado aquí en la reescritura "Command Center".
(function (document) {
    'use strict';

    function initHeader() {
        var h = new Date().getHours();
        var saludo = 'Buenas noches';
        if (h >= 5 && h < 12) saludo = 'Buenos días';
        else if (h >= 12 && h < 19) saludo = 'Buenas tardes';

        var greetingEl = document.getElementById('greeting-text');
        if (greetingEl) greetingEl.textContent = saludo + ', Rafael';

        var quotes = [
            '"La excelencia en el frontend no es casualidad; es la suma de disciplina y atención al detalle."',
            '"Un código limpio y veloz es la carta de presentación de un gran profesional."',
            '"Construyamos una plataforma que marque un antes y un después en La Paz."',
            '"El diseño resuelve problemas; el rendimiento crea experiencias inolvidables."'
        ];
        var quoteEl = document.getElementById('quote-text');
        if (quoteEl) quoteEl.textContent = quotes[Math.floor(Math.random() * quotes.length)];
    }

    function updateProgress() {
        var all = document.querySelectorAll('.todo-list input[type="checkbox"], #c14');
        var checked = Array.prototype.filter.call(all, function (c) { return c.checked; }).length;
        var pct = all.length ? Math.round((checked / all.length) * 100) : 0;

        var pctEl = document.getElementById('progress-percent');
        var fillEl = document.getElementById('progress-fill');
        if (pctEl) pctEl.textContent = pct + '%';
        if (fillEl) fillEl.style.width = pct + '%';

        var state = {};
        Array.prototype.forEach.call(all, function (c) { state[c.id] = c.checked; });
        try {
            localStorage.setItem('pittsburgh_onboarding_state', JSON.stringify(state));
        } catch (e) {
            // localStorage no disponible — el progreso simplemente no persiste.
        }
    }

    function saveSignature() {
        var sigEl = document.getElementById('sig-name');
        if (!sigEl) return;
        try {
            localStorage.setItem('pittsburgh_signature', sigEl.value);
        } catch (e) {
            // localStorage no disponible.
        }
    }

    function restoreState() {
        try {
            var raw = localStorage.getItem('pittsburgh_onboarding_state');
            if (raw) {
                var state = JSON.parse(raw);
                Object.keys(state).forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.checked = state[id];
                });
            }
            var sig = localStorage.getItem('pittsburgh_signature');
            if (sig) {
                var sigEl = document.getElementById('sig-name');
                if (sigEl) sigEl.value = sig;
            }
        } catch (e) {
            // localStorage no disponible — arranca en blanco.
        }
        updateProgress();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initHeader();
        restoreState();

        document.querySelectorAll('.todo-list input[type="checkbox"], #c14').forEach(function (cb) {
            cb.addEventListener('change', updateProgress);
        });

        var sigEl = document.getElementById('sig-name');
        if (sigEl) sigEl.addEventListener('input', saveSignature);
    });
})(document);
