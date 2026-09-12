// assets/js/onboarding-ui.js — PinturaPittsburgh_LaPazBCS
// Saludo dinámico, barra de cumplimiento y firma digital — compartido por
// TODOS los onboardings de colaboradores (Rafael, Moy, y los que se sumen).
// Antes vivía inline en el HTML (violaba la propia Regla de Oro que el
// documento le exige al colaborador) — migrado aquí en la reescritura
// "Command Center". Parametrizado por colaborador (Hito 19) vía
// <body data-collaborator-name="..."> y <body data-collaborator-id="...">
// — antes tenía "Rafael" y las claves de localStorage hardcodeadas, lo que
// habría mezclado el progreso de dos colaboradores distintos en el mismo
// navegador (ej. el Arquitecto revisando ambos onboardings).
(function (document) {
    'use strict';

    function idColaborador() {
        return document.body.getAttribute('data-collaborator-id') || 'default';
    }

    function claveEstado() {
        return 'pittsburgh_onboarding_state_' + idColaborador();
    }

    function claveFirma() {
        return 'pittsburgh_signature_' + idColaborador();
    }

    function initHeader() {
        var h = new Date().getHours();
        var saludo = 'Buenas noches';
        if (h >= 5 && h < 12) saludo = 'Buenos días';
        else if (h >= 12 && h < 19) saludo = 'Buenas tardes';

        var nombre = document.body.getAttribute('data-collaborator-name') || '';

        var greetingEl = document.getElementById('greeting-text');
        if (greetingEl) greetingEl.textContent = nombre ? (saludo + ', ' + nombre) : saludo;

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
            localStorage.setItem(claveEstado(), JSON.stringify(state));
        } catch (e) {
            // localStorage no disponible — el progreso simplemente no persiste.
        }
    }

    function saveSignature() {
        var sigEl = document.getElementById('sig-name');
        if (!sigEl) return;
        try {
            localStorage.setItem(claveFirma(), sigEl.value);
        } catch (e) {
            // localStorage no disponible.
        }
    }

    function restoreState() {
        try {
            var raw = localStorage.getItem(claveEstado());
            if (raw) {
                var state = JSON.parse(raw);
                Object.keys(state).forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.checked = state[id];
                });
            }
            var sig = localStorage.getItem(claveFirma());
            if (sig) {
                var sigEl = document.getElementById('sig-name');
                if (sigEl) sigEl.value = sig;
            }
        } catch (e) {
            // localStorage no disponible — arranca en blanco.
        }
        updateProgress();
    }

    // ── Suite de Autodiagnóstico (Frontend Audit Script, Hito 20) ───────────
    // Analiza una URL de MISMO ORIGEN (ej. la propia previsualización del
    // colaborador, ../preview-rafa/index.html o ../preview-moy/index.html)
    // vía fetch() + DOMParser — nunca ejecuta el código ajeno, solo lo lee
    // como texto/DOM. Verifica: (1) hooks data-* obligatorios, (2) cero
    // estilos inline, (3) cero !important en el CSS (inline + hojas
    // enlazadas del mismo origen), (4) proporción/aspect-ratio en imágenes.
    // Es análisis estático de lo que SÍ se puede inspeccionar sin renderizar
    // un navegador real — no reemplaza Lighthouse/axe (Etapa 5), los
    // complementa con retroalimentación inmediata dentro de la misma página.
    // Lista oficial de hooks del Data Contract (Hito 21) — ver
    // knowledge/07_UI_MODULOS_Y_PANTALLAS.md §3 para el detalle de cada uno.
    // data-social-item se agregó en este Hito por simetría con
    // data-banner-item/data-promo-code (antes solo banners y promos tenían
    // hook de ítem individual, publicaciones no).
    var HOOKS_OBLIGATORIOS = [
        '[data-banner-slider]',
        '[data-banner-template]',
        '[data-banner-item]',
        '[data-cta-link]',
        '[data-promo-section]',
        '[data-promo-template]',
        '[data-promo-code]',
        '[data-social-feed]',
        '[data-social-template]',
        '[data-social-item]',
        '[data-field]'
    ];

    function auditarDocumento(doc, cssCompleto) {
        var resultados = [];

        HOOKS_OBLIGATORIOS.forEach(function (selector) {
            var existe = doc.querySelectorAll(selector).length > 0;
            resultados.push({
                ok: existe,
                texto: 'Hook ' + selector + (existe ? ': encontrado' : ': NO encontrado')
            });
        });

        var inlineCount = doc.querySelectorAll('[style]').length;
        resultados.push({
            ok: inlineCount === 0,
            texto: inlineCount === 0
                ? 'Cero estilos inline (style="") en el documento'
                : inlineCount + ' elemento(s) con estilo inline detectado(s)'
        });

        var tieneImportant = /!important/i.test(cssCompleto);
        resultados.push({
            ok: !tieneImportant,
            texto: tieneImportant ? 'Se detectó al menos un "!important" en el CSS' : 'Cero uso de "!important" en el CSS analizado'
        });

        var imgs = doc.querySelectorAll('img');
        var sinProporcion = 0;
        imgs.forEach(function (img) {
            var tieneAtributos = img.hasAttribute('width') && img.hasAttribute('height');
            var clases = (img.getAttribute('class') || '').trim().split(/\s+/).filter(Boolean);
            var claseConAspecto = clases.some(function (c) {
                var patron = new RegExp('\\.' + c.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '[^{]*\\{[^}]*aspect-ratio', 'i');
                return patron.test(cssCompleto);
            });
            if (!tieneAtributos && !claseConAspecto) sinProporcion++;
        });
        resultados.push({
            ok: imgs.length === 0 || sinProporcion === 0,
            texto: imgs.length === 0
                ? 'Sin imágenes que evaluar en el documento'
                : (imgs.length - sinProporcion) + ' de ' + imgs.length + ' imagen(es) con width/height o aspect-ratio detectable'
        });

        return resultados;
    }

    function renderAuditoria(resultados, elLista, elAnillo, elEtiqueta) {
        elLista.innerHTML = '';
        var pasan = 0;

        resultados.forEach(function (r) {
            if (r.ok) pasan++;
            var li = document.createElement('li');
            li.className = 'cc-audit-list__item ' + (r.ok ? 'cc-audit-list__item--pass' : 'cc-audit-list__item--fail');

            var icono = document.createElement('span');
            icono.className = 'cc-audit-list__icon';
            icono.textContent = r.ok ? '✓' : '✗';

            var texto = document.createElement('span');
            texto.textContent = r.texto;

            li.appendChild(icono);
            li.appendChild(texto);
            elLista.appendChild(li);
        });

        var pct = resultados.length ? Math.round((pasan / resultados.length) * 100) : 0;
        elAnillo.textContent = pct + '%';
        elAnillo.className = 'cc-audit-score__ring ' + (pct >= 90 ? 'cc-audit-score__ring--pass' : pct >= 60 ? 'cc-audit-score__ring--warn' : 'cc-audit-score__ring--fail');
        elEtiqueta.textContent = pasan + ' de ' + resultados.length + ' verificaciones aprobadas';
    }

    function initAudit() {
        var btn = document.getElementById('audit-run-btn');
        var urlInput = document.getElementById('audit-url-input');
        var listaEl = document.getElementById('audit-results');
        var anilloEl = document.getElementById('audit-score-ring');
        var etiquetaEl = document.getElementById('audit-score-label');
        var statusEl = document.getElementById('audit-status');

        if (!btn || !urlInput || !listaEl || !anilloEl || !etiquetaEl) {
            return;
        }

        btn.addEventListener('click', function () {
            var url = urlInput.value.trim();
            if (!url) {
                return;
            }

            statusEl.textContent = 'Analizando ' + url + '...';
            statusEl.hidden = false;
            listaEl.innerHTML = '';

            fetch(url)
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.text();
                })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var estilosInline = Array.prototype.map.call(doc.querySelectorAll('style'), function (s) {
                        return s.textContent;
                    }).join('\n');

                    var hojas = Array.prototype.map.call(doc.querySelectorAll('link[rel="stylesheet"]'), function (link) {
                        var href = link.getAttribute('href');
                        if (!href) {
                            return Promise.resolve('');
                        }
                        var absoluta;
                        try {
                            absoluta = new URL(href, url).href;
                        } catch (e) {
                            return Promise.resolve('');
                        }
                        return fetch(absoluta).then(function (r) { return r.ok ? r.text() : ''; }).catch(function () { return ''; });
                    });

                    return Promise.all(hojas).then(function (textos) {
                        var cssCompleto = estilosInline + '\n' + textos.join('\n');
                        return auditarDocumento(doc, cssCompleto);
                    });
                })
                .then(function (resultados) {
                    statusEl.hidden = true;
                    renderAuditoria(resultados, listaEl, anilloEl, etiquetaEl);
                })
                .catch(function (err) {
                    statusEl.hidden = false;
                    statusEl.textContent = 'No fue posible analizar esa URL (' + err.message + '). Verifica que ya hiciste push a tu rama y que la ruta/URL es correcta y del mismo origen.';
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initHeader();
        restoreState();
        initAudit();

        document.querySelectorAll('.todo-list input[type="checkbox"], #c14').forEach(function (cb) {
            cb.addEventListener('change', updateProgress);
        });

        var sigEl = document.getElementById('sig-name');
        if (sigEl) sigEl.addEventListener('input', saveSignature);
    });
})(document);
