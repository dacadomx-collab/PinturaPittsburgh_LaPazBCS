// assets/js/catalog-render.js — PinturaPittsburgh_LaPazBCS
// Renderiza el catálogo facetado (ARF-Grid) consumiendo api/catalogo_listar.php
// (Contrato 3). El filtrado ocurre en el servidor: cada click en un facet
// vuelve a pedir la página con el filtro aplicado como query string.
(function (global, document) {
    'use strict';

    var LABELS = {
        linea: {
            speedhide: 'Speedhide',
            manor_hall: 'Manor Hall',
            perma_crete: 'Perma-Crete',
            pitt_glaze: 'Pitt-Glaze',
            otra: 'Otra línea'
        },
        sustrato: {
            concreto_costero: 'Concreto Costero',
            enjarre_yeso: 'Enjarre / Yeso',
            tabla_roca: 'Tabla Roca',
            madera_marina: 'Madera Marina',
            herreria: 'Herrería',
            piso_alto_transito: 'Piso Alto Tránsito'
        },
        grado_brillo: {
            mate: 'Mate',
            eggshell: 'Eggshell',
            satinado: 'Satinado',
            semibrillante: 'Semibrillante',
            brillante: 'Brillante'
        },
        volumen: {
            cuarto_galon: 'Cuarto de Galón',
            galon: 'Galón (3.78 L)',
            cubeta: 'Cubeta (19 L)'
        }
    };

    // Universo fijo de facets — coincide con los ENUM de `productos` en
    // database/001_schema_inicial.sql. Se muestran todas las opciones aunque
    // la página actual no tenga productos en alguna de ellas (las categorías
    // son taxonomía del negocio, no un derivado de los resultados en pantalla).
    var FACET_VALUES = {
        sustrato: ['concreto_costero', 'enjarre_yeso', 'tabla_roca', 'madera_marina', 'herreria', 'piso_alto_transito'],
        grado_brillo: ['mate', 'eggshell', 'satinado', 'semibrillante', 'brillante']
    };

    var activeFilters = { sustrato: null, grado_brillo: null };

    function formatMoney(value) {
        return '$' + Number(value).toLocaleString('es-MX', { minimumFractionDigits: 0 });
    }

    function precioRango(presentaciones) {
        if (!presentaciones || presentaciones.length === 0) {
            return 'Consultar disponibilidad';
        }
        var precios = presentaciones.map(function (p) { return p.precio; });
        var min = Math.min.apply(null, precios);
        var max = Math.max.apply(null, precios);
        return min === max ? formatMoney(min) : formatMoney(min) + ' – ' + formatMoney(max);
    }

    function buildCard(product) {
        var card = document.createElement('article');
        card.className = 'product-card card';

        var badge = document.createElement('span');
        badge.className = 'product-card__badge';
        badge.textContent = LABELS.linea[product.linea] || product.linea;
        card.appendChild(badge);

        var media = document.createElement('div');
        media.className = 'catalog-media';
        media.setAttribute('aria-hidden', 'true');
        card.appendChild(media);

        var nombre = document.createElement('h3');
        nombre.className = 'product-card__nombre';
        nombre.textContent = product.nombre;
        card.appendChild(nombre);

        var meta = document.createElement('p');
        meta.className = 'product-card__meta';
        meta.textContent = (LABELS.sustrato[product.sustrato] || '') + ' · ' + (LABELS.grado_brillo[product.grado_brillo] || '');
        card.appendChild(meta);

        var precio = document.createElement('p');
        precio.className = 'product-card__precio';
        precio.textContent = precioRango(product.presentaciones);
        card.appendChild(precio);

        var quickView = document.createElement('div');
        quickView.className = 'product-card__quick-view';
        var volumenes = (product.presentaciones || []).map(function (p) {
            return LABELS.volumen[p.volumen] || p.volumen;
        }).join(' · ') || 'Sin presentaciones registradas';
        var pVol = document.createElement('p');
        pVol.textContent = 'Presentaciones: ' + volumenes;
        quickView.appendChild(pVol);

        var verFicha = document.createElement('a');
        verFicha.href = 'producto.php?id=' + encodeURIComponent(String(product.id));
        verFicha.className = 'product-card__link';
        verFicha.textContent = 'Ver ficha completa →';
        quickView.appendChild(verFicha);

        card.appendChild(quickView);

        return card;
    }

    function buildQueryString() {
        var params = [];
        if (activeFilters.sustrato) {
            params.push('sustrato=' + encodeURIComponent(activeFilters.sustrato));
        }
        if (activeFilters.grado_brillo) {
            params.push('grado_brillo=' + encodeURIComponent(activeFilters.grado_brillo));
        }
        return params.length ? '?' + params.join('&') : '';
    }

    function render() {
        var grid = document.getElementById('catalogo-grid');
        if (!grid) {
            return;
        }

        grid.innerHTML = '';
        var loading = document.createElement('p');
        loading.className = 'arf-col-4';
        loading.textContent = 'Cargando catálogo...';
        grid.appendChild(loading);

        fetch('api/catalogo_listar.php' + buildQueryString())
            .then(function (res) { return res.json(); })
            .then(function (body) {
                grid.innerHTML = '';

                if (body.status !== 'success') {
                    var errorMsg = document.createElement('p');
                    errorMsg.className = 'arf-col-4';
                    errorMsg.textContent = body.message || 'No fue posible cargar el catálogo.';
                    grid.appendChild(errorMsg);
                    return;
                }

                var productos = body.data.productos || [];

                if (productos.length === 0) {
                    var empty = document.createElement('p');
                    empty.className = 'arf-col-4';
                    empty.textContent = 'No hay productos con esa combinación de filtros por ahora.';
                    grid.appendChild(empty);
                    return;
                }

                productos.forEach(function (producto) {
                    grid.appendChild(buildCard(producto));
                });
            })
            .catch(function () {
                grid.innerHTML = '';
                var offline = document.createElement('p');
                offline.className = 'arf-col-4';
                offline.textContent = 'No fue posible conectar con el catálogo. Intenta de nuevo más tarde.';
                grid.appendChild(offline);
            });
    }

    function buildFacetGroup(containerId, facetKey) {
        var container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        FACET_VALUES[facetKey].forEach(function (value) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'facet-chip';
            chip.setAttribute('aria-pressed', 'false');
            chip.textContent = LABELS[facetKey][value] || value;
            chip.addEventListener('click', function () {
                var isActive = activeFilters[facetKey] === value;
                activeFilters[facetKey] = isActive ? null : value;

                Array.from(container.querySelectorAll('.facet-chip')).forEach(function (btn) {
                    btn.setAttribute('aria-pressed', 'false');
                });
                if (!isActive) {
                    chip.setAttribute('aria-pressed', 'true');
                }

                render();
            });
            container.appendChild(chip);
        });
    }

    function init() {
        if (!document.getElementById('catalogo-grid')) {
            return;
        }
        buildFacetGroup('facet-sustrato', 'sustrato');
        buildFacetGroup('facet-brillo', 'grado_brillo');
        render();
    }

    global.PPCatalogRender = { init: init };
})(window, document);
