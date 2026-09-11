// assets/js/producto-page.js — PinturaPittsburgh_LaPazBCS
// Ficha de Producto Individual (PDP): fetch a api/catalogo_detalle.php,
// selector de presentación, precio dinámico y alta al carrito.
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
        },
        propiedad: {
            anti_salitre: 'Anti-Salitre',
            zero_voc: 'Zero-VOC',
            one_coat_hide: 'Cubrimiento en Una Mano',
            cool_surface: 'Reflectancia Solar (Cool Surface)'
        }
    };

    function formatMoney(value) {
        return '$' + Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getProductoId() {
        var params = new URLSearchParams(window.location.search);
        var id = parseInt(params.get('id'), 10);
        return isNaN(id) || id <= 0 ? null : id;
    }

    var presentacionSeleccionada = null;

    function renderPresentaciones(presentaciones) {
        var contenedor = document.getElementById('pdp-presentaciones');
        contenedor.innerHTML = '';

        if (presentaciones.length === 0) {
            contenedor.textContent = 'Sin presentaciones disponibles por ahora.';
            return;
        }

        presentaciones.forEach(function (p, index) {
            var label = document.createElement('label');
            label.className = 'presentacion-option';

            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'presentacion';
            radio.value = String(p.id);
            radio.checked = index === 0;
            radio.addEventListener('change', function () {
                presentacionSeleccionada = p;
                actualizarPrecio();
            });

            var texto = document.createElement('span');
            texto.textContent = (LABELS.volumen[p.volumen] || p.volumen) + ' — ' + formatMoney(p.precio) +
                (p.stock <= 0 ? ' (agotado)' : '');

            if (p.stock <= 0) {
                radio.disabled = true;
            }

            label.appendChild(radio);
            label.appendChild(texto);
            contenedor.appendChild(label);
        });

        presentacionSeleccionada = presentaciones.find(function (p) { return p.stock > 0; }) || presentaciones[0];
    }

    function actualizarPrecio() {
        var cantidadInput = document.getElementById('pdp-cantidad');
        var cantidad = Math.max(1, parseInt(cantidadInput.value, 10) || 1);
        var precioEl = document.getElementById('pdp-precio-total');

        if (!presentacionSeleccionada) {
            precioEl.textContent = '—';
            return;
        }

        precioEl.textContent = formatMoney(presentacionSeleccionada.precio * cantidad);
    }

    function renderPropiedades(propiedades) {
        var contenedor = document.getElementById('pdp-propiedades');
        contenedor.innerHTML = '';

        (propiedades || []).forEach(function (clave) {
            var badge = document.createElement('span');
            badge.className = 'propiedad-badge';
            badge.textContent = LABELS.propiedad[clave] || clave;
            contenedor.appendChild(badge);
        });
    }

    function renderFichas(producto) {
        var contenedor = document.getElementById('pdp-fichas');
        contenedor.innerHTML = '';

        if (producto.ficha_tecnica_pdf_url) {
            var linkTecnica = document.createElement('a');
            linkTecnica.href = producto.ficha_tecnica_pdf_url;
            linkTecnica.className = 'btn';
            linkTecnica.target = '_blank';
            linkTecnica.rel = 'noopener';
            linkTecnica.textContent = 'Ficha Técnica (PDF)';
            contenedor.appendChild(linkTecnica);
        }

        if (producto.ficha_seguridad_pdf_url) {
            var linkSeguridad = document.createElement('a');
            linkSeguridad.href = producto.ficha_seguridad_pdf_url;
            linkSeguridad.className = 'btn';
            linkSeguridad.target = '_blank';
            linkSeguridad.rel = 'noopener';
            linkSeguridad.textContent = 'Hoja de Seguridad (PDF)';
            contenedor.appendChild(linkSeguridad);
        }

        if (!producto.ficha_tecnica_pdf_url && !producto.ficha_seguridad_pdf_url) {
            contenedor.textContent = 'Fichas técnicas disponibles próximamente.';
        }
    }

    function renderProducto(producto) {
        document.title = producto.nombre + ' — PinturaPittsburgh';
        document.getElementById('pdp-nombre').textContent = producto.nombre;
        document.getElementById('pdp-badge').textContent = LABELS.linea[producto.linea] || producto.linea;
        document.getElementById('pdp-meta').textContent =
            (LABELS.sustrato[producto.sustrato] || '') + ' · ' + (LABELS.grado_brillo[producto.grado_brillo] || '');
        document.getElementById('pdp-descripcion').textContent = producto.descripcion || '';

        var media = document.getElementById('pdp-media');
        if (producto.can_cut_url) {
            media.src = producto.can_cut_url;
            media.alt = producto.nombre;
        } else {
            media.remove();
        }

        renderPropiedades(producto.propiedades_funcionales);
        renderPresentaciones(producto.presentaciones);
        renderFichas(producto);
        actualizarPrecio();
    }

    function cargarProducto() {
        var id = getProductoId();
        var contenedorPrincipal = document.getElementById('pdp-contenido');
        var errorEl = document.getElementById('pdp-error');

        if (id === null) {
            contenedorPrincipal.hidden = true;
            errorEl.hidden = false;
            errorEl.textContent = 'Producto no especificado. Vuelve al catálogo e intenta de nuevo.';
            return;
        }

        fetch('api/catalogo_detalle.php?id=' + encodeURIComponent(String(id)))
            .then(function (res) { return res.json().then(function (body) { return { res: res, body: body }; }); })
            .then(function (r) {
                if (!r.res.ok || r.body.status !== 'success') {
                    throw new Error(r.body.message || 'Producto no encontrado.');
                }
                renderProducto(r.body.data.producto);
            })
            .catch(function (err) {
                contenedorPrincipal.hidden = true;
                errorEl.hidden = false;
                errorEl.textContent = err.message || 'No fue posible cargar el producto.';
            });
    }

    function init() {
        if (!document.getElementById('pdp-contenido')) {
            return;
        }

        cargarProducto();

        document.getElementById('pdp-cantidad').addEventListener('input', actualizarPrecio);

        document.getElementById('pdp-agregar-btn').addEventListener('click', function () {
            var statusEl = document.getElementById('pdp-carrito-status');

            if (!presentacionSeleccionada || presentacionSeleccionada.stock <= 0) {
                statusEl.hidden = false;
                statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                statusEl.textContent = 'Selecciona una presentación disponible.';
                return;
            }

            var cantidad = Math.max(1, parseInt(document.getElementById('pdp-cantidad').value, 10) || 1);
            var nombre = document.getElementById('pdp-nombre').textContent;

            global.PPCart.add({
                producto_presentacion_id: presentacionSeleccionada.id,
                nombre: nombre,
                volumen: LABELS.volumen[presentacionSeleccionada.volumen] || presentacionSeleccionada.volumen,
                precio: presentacionSeleccionada.precio,
                cantidad: cantidad
            });

            statusEl.hidden = false;
            statusEl.className = 'postal-bar__result postal-bar__result--ok';
            statusEl.innerHTML = 'Agregado a tu pedido. <a href="checkout.html">Ir a checkout →</a>';
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})(window, document);
