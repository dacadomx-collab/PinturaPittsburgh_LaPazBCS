// assets/js/catalog-data.js — PinturaPittsburgh_LaPazBCS
//
// ⚠️ DATOS DE DEMOSTRACIÓN (DEMO_DATA). Estructura idéntica a las columnas de
// `productos` / `producto_presentaciones` en database/001_schema_inicial.sql
// (Directiva 1 — "Validación espejo") para que este archivo se sustituya por
// una respuesta real de `GET api/catalogo_listar.php` sin cambiar el resto del
// código. Precios y existencias son de referencia — no representan la lista
// de precios vigente en tienda ni compromisos de disponibilidad reales.
(function (global) {
    'use strict';

    var DEMO_PRODUCTS = [
        {
            id: 1,
            nombre: 'Speedhide Interior/Exterior Satinado',
            linea: 'speedhide',
            sustrato: 'enjarre_yeso',
            grado_brillo: 'satinado',
            can_cut_url: null,
            presentaciones: [
                { volumen: 'cuarto_galon', sku: 'SPH-SAT-QG', precio: 180 },
                { volumen: 'galon', sku: 'SPH-SAT-GAL', precio: 620 },
                { volumen: 'cubeta', sku: 'SPH-SAT-CUB', precio: 2850 }
            ]
        },
        {
            id: 2,
            nombre: 'Speedhide One-Coat Hide Mate Exterior',
            linea: 'speedhide',
            sustrato: 'concreto_costero',
            grado_brillo: 'mate',
            can_cut_url: null,
            presentaciones: [
                { volumen: 'galon', sku: 'SPH-OCH-GAL', precio: 690 },
                { volumen: 'cubeta', sku: 'SPH-OCH-CUB', precio: 3150 }
            ]
        },
        {
            id: 3,
            nombre: 'Manor Hall Interior Eggshell',
            linea: 'manor_hall',
            sustrato: 'tabla_roca',
            grado_brillo: 'eggshell',
            can_cut_url: null,
            presentaciones: [
                { volumen: 'cuarto_galon', sku: 'MH-EGG-QG', precio: 210 },
                { volumen: 'galon', sku: 'MH-EGG-GAL', precio: 740 }
            ]
        },
        {
            id: 4,
            nombre: 'Perma-Crete Elastomérico Anti-Salitre',
            linea: 'perma_crete',
            sustrato: 'concreto_costero',
            grado_brillo: 'mate',
            can_cut_url: null,
            presentaciones: [
                { volumen: 'galon', sku: 'PC-ELA-GAL', precio: 890 },
                { volumen: 'cubeta', sku: 'PC-ELA-CUB', precio: 4100 }
            ]
        },
        {
            id: 5,
            nombre: 'Pitt-Glaze Esmalte Semibrillante para Herrería',
            linea: 'pitt_glaze',
            sustrato: 'herreria',
            grado_brillo: 'semibrillante',
            can_cut_url: null,
            presentaciones: [
                { volumen: 'cuarto_galon', sku: 'PG-SEM-QG', precio: 260 },
                { volumen: 'galon', sku: 'PG-SEM-GAL', precio: 910 }
            ]
        },
        {
            id: 6,
            nombre: 'Speedhide Piso de Alto Tránsito',
            linea: 'speedhide',
            sustrato: 'piso_alto_transito',
            grado_brillo: 'semibrillante',
            can_cut_url: null,
            presentaciones: [
                { volumen: 'galon', sku: 'SPH-PAT-GAL', precio: 780 },
                { volumen: 'cubeta', sku: 'SPH-PAT-CUB', precio: 3600 }
            ]
        }
    ];

    global.PPCatalogData = { products: DEMO_PRODUCTS };
})(window);
