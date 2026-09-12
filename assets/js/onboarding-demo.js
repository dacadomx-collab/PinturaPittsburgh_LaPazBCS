// assets/js/onboarding-demo.js — PinturaPittsburgh_LaPazBCS
// Implementación de referencia "Data → Template → Render" para que Rafael
// vea, funcionando de verdad, el patrón que debe replicar: fetch() real a
// los 3 endpoints públicos, <template> nativo clonado por item, y los 4
// estados de UI obligatorios (Loading, Empty, Success, Error).
//
// Ahora mismo esto mostrará el estado "Error" en las 3 secciones — es
// correcto y esperado: la BD de staging todavía no tiene conexión confirmada
// (ver CLAUDE.md §6). El día que exista, estas mismas secciones pasarán a
// "Success" o "Empty" sin tocar una línea de este archivo.
(function (document) {
    'use strict';

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

    function cargarSeccion(config) {
        var seccion = document.querySelector(config.selectorSeccion);
        if (!seccion) {
            return;
        }

        var templateEl = seccion.querySelector(config.selectorTemplate);
        var contenedorExito = seccion.querySelector('[data-state="success"]');

        mostrarEstado(seccion, 'loading');

        fetch(config.endpoint)
            .then(function (res) {
                return res.json().then(function (body) { return { res: res, body: body }; });
            })
            .then(function (r) {
                if (!r.res.ok || r.body.status !== 'success') {
                    throw new Error(r.body.message || 'Respuesta no exitosa');
                }
                var items = r.body.data[config.dataKey] || [];
                if (items.length === 0) {
                    mostrarEstado(seccion, 'empty');
                    return;
                }
                renderLista(contenedorExito, templateEl, items, config.campos, config.posProceso);
                mostrarEstado(seccion, 'success');
            })
            .catch(function () {
                mostrarEstado(seccion, 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cargarSeccion({
            selectorSeccion: '[data-banner-slider]',
            selectorTemplate: '[data-banner-template]',
            endpoint: '../api/banners_listar.php',
            dataKey: 'banners',
            campos: ['eyebrow', 'titulo', 'descripcion', 'imagen_url', 'cta_texto'],
            posProceso: function (raiz, item) {
                var cta = raiz.querySelector('[data-cta-link]');
                if (cta) {
                    cta.href = item.cta_url || '#';
                }
            }
        });

        cargarSeccion({
            selectorSeccion: '[data-promo-section]',
            selectorTemplate: '[data-promo-template]',
            endpoint: '../api/promociones_listar.php',
            dataKey: 'promociones',
            campos: ['badge', 'descripcion', 'fecha_inicio', 'fecha_fin'],
            posProceso: function (raiz, item) {
                raiz.setAttribute('data-promo-code', item.codigo || '');
            }
        });

        cargarSeccion({
            selectorSeccion: '[data-social-feed]',
            selectorTemplate: '[data-social-template]',
            endpoint: '../api/publicaciones_listar.php',
            dataKey: 'publicaciones',
            campos: ['media_url', 'texto', 'publicado_en']
        });
    });
})(document);
