// assets/js/onboarding-demo.js — PinturaPittsburgh_LaPazBCS
// Implementación de referencia "Data → Template → Render" para que el
// colaborador vea, funcionando de verdad, el patrón que debe replicar en su
// propia rama: fetch() real a los 3 endpoints públicos (o a
// mock-data/mock-data.json en "Modo Mock Local"), <template> nativo clonado
// por ítem, y los 4 estados de UI obligatorios (Loading, Empty, Success,
// Error).
//
// Toggle Modo API Real / Modo Mock Local (Hito 20): útil cuando la BD de
// staging no responde o cuando se quiere ver rápido el patrón con datos de
// ejemplo sin depender de red — nunca cambia el contrato de datos, solo la
// FUENTE (misma forma exacta en ambos casos, ver mock-data/mock-data.json).
(function (document) {
    'use strict';

    var MODE_STORAGE_KEY = 'pittsburgh_onboarding_mode';
    var MOCK_URL = '../mock-data/mock-data.json';

    function mostrarEstado(seccion, estado) {
        seccion.querySelectorAll('[data-state]').forEach(function (el) {
            el.hidden = el.getAttribute('data-state') !== estado;
        });
    }

    function poblarCampo(raiz, campo, valor) {
        var el = raiz.querySelector('[data-field="' + campo + '"]');
        if (!el) {
            return;
        }
        if (el.tagName === 'IMG') {
            el.src = valor || '';
        } else {
            el.textContent = valor || '';
        }
    }

    function renderLista(contenedorExito, templateEl, items, campos, posProceso) {
        contenedorExito.innerHTML = '';
        items.forEach(function (item) {
            var frag = templateEl.content.cloneNode(true);
            var raiz = frag.firstElementChild;
            campos.forEach(function (campo) { poblarCampo(raiz, campo, item[campo]); });
            if (typeof posProceso === 'function') {
                posProceso(raiz, item);
            }
            contenedorExito.appendChild(raiz);
        });
    }

    function obtenerModoActual() {
        try {
            return localStorage.getItem(MODE_STORAGE_KEY) || 'real';
        } catch (e) {
            return 'real';
        }
    }

    // Cache del JSON mock en memoria — 3 secciones lo consumen, no hace
    // falta pedirlo 3 veces por carga de página.
    var mockPromesa = null;
    function obtenerMock() {
        if (!mockPromesa) {
            mockPromesa = fetch(MOCK_URL).then(function (res) {
                if (!res.ok) {
                    throw new Error('No se pudo leer mock-data.json');
                }
                return res.json();
            });
        }
        return mockPromesa;
    }

    function cargarSeccion(config) {
        var seccion = document.querySelector(config.selectorSeccion);
        if (!seccion) {
            return;
        }

        var templateEl = seccion.querySelector(config.selectorTemplate);
        var contenedorExito = seccion.querySelector('[data-state="success"]');
        var modo = obtenerModoActual();

        mostrarEstado(seccion, 'loading');

        var promesaDatos;
        if (modo === 'mock') {
            promesaDatos = obtenerMock().then(function (json) {
                var items = json[config.dataKeyMock || config.dataKey] || [];
                return { ok: true, items: items };
            });
        } else {
            promesaDatos = fetch(config.endpoint)
                .then(function (res) {
                    return res.json().then(function (body) { return { res: res, body: body }; });
                })
                .then(function (r) {
                    if (!r.res.ok || r.body.status !== 'success') {
                        throw new Error(r.body.message || 'Respuesta no exitosa');
                    }
                    return { ok: true, items: r.body.data[config.dataKey] || [] };
                });
        }

        promesaDatos
            .then(function (r) {
                if (r.items.length === 0) {
                    mostrarEstado(seccion, 'empty');
                    return;
                }
                renderLista(contenedorExito, templateEl, r.items, config.campos, config.posProceso);
                mostrarEstado(seccion, 'success');
            })
            .catch(function () {
                mostrarEstado(seccion, 'error');
            });
    }

    var SECCIONES = [
        {
            selectorSeccion: '[data-banner-slider]',
            selectorTemplate: '[data-banner-template]',
            endpoint: '../api/banners_listar.php',
            dataKey: 'banners',
            dataKeyMock: 'banners',
            campos: ['eyebrow', 'titulo', 'descripcion', 'imagen_url', 'cta_texto'],
            posProceso: function (raiz, item) {
                raiz.setAttribute('data-banner-item', String(item.id));
                var cta = raiz.querySelector('[data-cta-link]');
                if (cta) {
                    cta.href = item.cta_url || '#';
                }
            }
        },
        {
            selectorSeccion: '[data-promo-section]',
            selectorTemplate: '[data-promo-template]',
            endpoint: '../api/promociones_listar.php',
            dataKey: 'promociones',
            dataKeyMock: 'promotions',
            campos: ['badge', 'descripcion', 'fecha_inicio', 'fecha_fin'],
            posProceso: function (raiz, item) {
                raiz.setAttribute('data-promo-code', item.codigo || '');
            }
        },
        {
            selectorSeccion: '[data-social-feed]',
            selectorTemplate: '[data-social-template]',
            endpoint: '../api/publicaciones_listar.php',
            dataKey: 'publicaciones',
            dataKeyMock: 'publications',
            campos: ['media_url', 'texto', 'publicado_en']
        }
    ];

    function cargarTodo() {
        SECCIONES.forEach(cargarSeccion);
    }

    function initModeToggle() {
        var botones = document.querySelectorAll('[data-mode-btn]');
        if (botones.length === 0) {
            return;
        }

        function actualizarBotones(modo) {
            botones.forEach(function (btn) {
                var activo = btn.getAttribute('data-mode-btn') === modo;
                btn.setAttribute('aria-pressed', String(activo));
            });
        }

        actualizarBotones(obtenerModoActual());

        botones.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modo = btn.getAttribute('data-mode-btn');
                try {
                    localStorage.setItem(MODE_STORAGE_KEY, modo);
                } catch (e) {
                    // localStorage no disponible — el modo solo dura esta carga.
                }
                actualizarBotones(modo);
                cargarTodo();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initModeToggle();
        cargarTodo();
    });
})(document);
