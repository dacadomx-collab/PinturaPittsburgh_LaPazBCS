// assets/js/catalog-render.js — PinturaPittsburgh_LaPazBCS
// Renderiza el catálogo facetado (ARF-Grid) a partir de PPCatalogData.
// Cuando exista api/catalogo_listar.php, sustituir PPCatalogData.products por
// el resultado de fetch('api/catalogo_listar.php').then(r => r.json()).
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

    var activeFilters = { sustrato: null, grado_brillo: null };

    function formatMoney(value) {
        return '$' + Number(value).toLocaleString('es-MX', { minimumFractionDigits: 0 });
    }

    function precioRango(presentaciones) {
        var precios = presentaciones.map(function (p) { return p.precio; });
        var min = Math.min.apply(null, precios);
        var max = Math.max.apply(null, precios);
        return min === max ? formatMoney(min) : formatMoney(min) + ' – ' + formatMoney(max);
    }

    function productMatchesFilters(product) {
        if (activeFilters.sustrato && product.sustrato !== activeFilters.sustrato) {
            return false;
        }
        if (activeFilters.grado_brillo && product.grado_brillo !== activeFilters.grado_brillo) {
            return false;
        }
        return true;
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
        var volumenes = product.presentaciones.map(function (p) {
            return LABELS.volumen[p.volumen] || p.volumen;
        }).join(' · ');
        var pVol = document.createElement('p');
        pVol.textContent = 'Presentaciones: ' + volumenes;
        quickView.appendChild(pVol);
        card.appendChild(quickView);

        return card;
    }

    function render() {
        var grid = document.getElementById('catalogo-grid');
        if (!grid) {
            return;
        }

        grid.innerHTML = '';
        var products = (global.PPCatalogData && global.PPCatalogData.products) || [];
        var visibles = products.filter(productMatchesFilters);

        if (visibles.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'arf-col-4';
            empty.textContent = 'No hay productos con esa combinación de filtros por ahora.';
            grid.appendChild(empty);
            return;
        }

        visibles.forEach(function (product) {
            grid.appendChild(buildCard(product));
        });
    }

    function buildFacetGroup(containerId, facetKey) {
        var container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        var products = (global.PPCatalogData && global.PPCatalogData.products) || [];
        var values = Array.from(new Set(products.map(function (p) { return p[facetKey]; }).filter(Boolean)));

        values.forEach(function (value) {
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
