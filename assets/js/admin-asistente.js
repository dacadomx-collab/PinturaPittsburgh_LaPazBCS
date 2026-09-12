// assets/js/admin-asistente.js — PinturaPittsburgh_LaPazBCS
// Wiring de admin/asistente.php: consumo de api/asistente_ia.php (Contrato 7)
// y transferencia del resultado hacia admin/social.php para revisión humana.
(function (global, document) {
    'use strict';

    var TRANSFER_KEY = 'pp_social_transfer';

    function cargarProductos() {
        var select = document.getElementById('ia-producto');
        if (!select) {
            return;
        }

        fetch('../api/catalogo_listar.php')
            .then(function (res) { return res.json(); })
            .then(function (body) {
                if (body.status !== 'success') {
                    return;
                }
                (body.data.productos || []).forEach(function (producto) {
                    var option = document.createElement('option');
                    option.value = producto.id;
                    option.textContent = producto.nombre;
                    select.appendChild(option);
                });
            })
            .catch(function () {
                // El selector queda vacío — el usuario no podrá generar contenido sin producto_id.
            });
    }

    function actualizarVisibilidadPlataforma() {
        var tipo = document.getElementById('ia-tipo').value;
        var grupoPlataforma = document.getElementById('ia-plataforma-grupo');
        grupoPlataforma.hidden = tipo !== 'copy_publicitario';
    }

    function manejarEnvio(event) {
        event.preventDefault();

        var tipo = document.getElementById('ia-tipo').value;
        var productoId = document.getElementById('ia-producto').value;
        var plataforma = document.getElementById('ia-plataforma').value;
        var arquetipo = document.getElementById('ia-arquetipo').value;

        var outputBox = document.getElementById('ia-output');
        var outputTexto = document.getElementById('ia-output-texto');
        var statusEl = document.getElementById('ia-status');
        var accionesEl = document.getElementById('ia-output-actions');

        if (!productoId) {
            statusEl.hidden = false;
            statusEl.className = 'postal-bar__result postal-bar__result--blocked';
            statusEl.textContent = 'Selecciona un producto del catálogo.';
            return;
        }

        statusEl.hidden = false;
        statusEl.className = 'postal-bar__result';
        statusEl.textContent = 'Generando contenido...';
        outputBox.hidden = true;
        accionesEl.hidden = true;

        var body = {
            tipo: tipo,
            producto_id: parseInt(productoId, 10),
            arquetipo_objetivo: arquetipo || null
        };
        if (tipo === 'copy_publicitario') {
            body.plataforma = plataforma;
        }

        global.PPAdminAuth.authFetch('../api/asistente_ia.php', {
            method: 'POST',
            body: JSON.stringify(body)
        })
            .then(function (res) { return res.json(); })
            .then(function (resBody) {
                if (resBody.status !== 'success') {
                    statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                    statusEl.textContent = resBody.message || 'No fue posible generar el contenido.';
                    return;
                }

                statusEl.hidden = true;
                outputBox.hidden = false;
                accionesEl.hidden = false;
                outputTexto.textContent = resBody.data.contenido_generado;
                outputTexto.dataset.plataforma = tipo === 'copy_publicitario' ? plataforma : 'web';
                outputTexto.dataset.productoId = productoId;
            })
            .catch(function () {
                statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                statusEl.textContent = 'No fue posible conectar con el servidor.';
            });
    }

    function copiarContenido() {
        var texto = document.getElementById('ia-output-texto').textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto);
        }
    }

    function transferirAPublicador() {
        var outputTexto = document.getElementById('ia-output-texto');
        var datos = {
            texto: outputTexto.textContent,
            plataforma: outputTexto.dataset.plataforma,
            producto_id: outputTexto.dataset.productoId
        };
        sessionStorage.setItem(TRANSFER_KEY, JSON.stringify(datos));
        window.location.href = 'social.php';
    }

    function init() {
        var form = document.getElementById('ia-form');
        if (!form) {
            return;
        }

        cargarProductos();
        actualizarVisibilidadPlataforma();

        document.getElementById('ia-tipo').addEventListener('change', actualizarVisibilidadPlataforma);
        form.addEventListener('submit', manejarEnvio);
        document.getElementById('ia-copiar-btn').addEventListener('click', copiarContenido);
        document.getElementById('ia-transferir-btn').addEventListener('click', transferirAPublicador);
    }

    global.PPAdminAsistente = { init: init };
})(window, document);
