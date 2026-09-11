// assets/js/admin-social.js — PinturaPittsburgh_LaPazBCS
// Wiring de admin/social.html: previsualizador en vivo, envío a
// api/social_publicar.php (Contrato 6) y feed de historial.
(function (global, document) {
    'use strict';

    var LIMITE_RECOMENDADO = { facebook: 700, instagram: 220 };
    var TRANSFER_KEY = 'pp_social_transfer';

    var ESTADO_LABELS = {
        borrador: 'Borrador',
        programada: 'Programada',
        publicada: 'Publicada',
        fallida: 'Fallida'
    };

    function actualizarPreview() {
        var texto = document.getElementById('social-texto').value;
        var mediaUrl = document.getElementById('social-media-url').value;

        document.getElementById('preview-fb-caption').textContent = texto;
        document.getElementById('preview-ig-caption').textContent = texto;

        ['preview-fb-media', 'preview-ig-media'].forEach(function (id) {
            var img = document.getElementById(id);
            if (mediaUrl) {
                img.src = mediaUrl;
                img.hidden = false;
            } else {
                img.hidden = true;
            }
        });

        actualizarContador('facebook', 'preview-fb-counter', texto);
        actualizarContador('instagram', 'preview-ig-counter', texto);
    }

    function actualizarContador(plataforma, elId, texto) {
        var el = document.getElementById(elId);
        var limite = LIMITE_RECOMENDADO[plataforma];
        var longitud = texto.length;
        el.textContent = longitud + ' / ' + limite + ' caracteres recomendados';
        el.className = 'char-counter' + (longitud > limite ? ' char-counter--over' : '');
    }

    function cargarProductos() {
        var select = document.getElementById('social-producto');
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
                // El selector queda con solo la opción "Sin producto vinculado" — no bloquea el formulario.
            });
    }

    function publicarEnPlataforma(plataforma, body) {
        var payload = Object.assign({}, body, { plataforma: plataforma });
        return global.PPAdminAuth.authFetch('../api/social_publicar.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(function (res) { return res.json(); });
    }

    function manejarEnvio(event) {
        event.preventDefault();

        var plataformaSeleccion = document.getElementById('social-plataforma').value;
        var texto = document.getElementById('social-texto').value.trim();
        var mediaUrl = document.getElementById('social-media-url').value.trim();
        var productoId = document.getElementById('social-producto').value || null;
        var publicarAhora = document.getElementById('social-publicar-ahora').checked;
        var programadoPara = publicarAhora ? null : (document.getElementById('social-programado').value || null);

        var statusEl = document.getElementById('social-status');
        statusEl.hidden = false;
        statusEl.className = 'postal-bar__result';
        statusEl.textContent = 'Enviando...';

        var body = {
            texto: texto,
            media_url: mediaUrl,
            producto_id: productoId ? parseInt(productoId, 10) : null,
            programado_para: programadoPara
        };

        var plataformas = plataformaSeleccion === 'ambas' ? ['facebook', 'instagram'] : [plataformaSeleccion];

        Promise.all(plataformas.map(function (p) { return publicarEnPlataforma(p, body); }))
            .then(function (resultados) {
                var mensajes = resultados.map(function (r, i) {
                    return plataformas[i] + ': ' + (r.message || r.status);
                });
                var todasOk = resultados.every(function (r) { return r.status === 'success'; });

                statusEl.className = 'postal-bar__result ' + (todasOk ? 'postal-bar__result--ok' : 'postal-bar__result--blocked');
                statusEl.textContent = mensajes.join(' · ');

                cargarHistorial();
            })
            .catch(function () {
                statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                statusEl.textContent = 'No fue posible conectar con el servidor.';
            });
    }

    function cargarHistorial() {
        var tbody = document.getElementById('social-historial-tbody');
        if (!tbody) {
            return;
        }

        global.PPAdminAuth.authFetch('../api/admin/social_historial.php', { method: 'GET' })
            .then(function (res) { return res.json(); })
            .then(function (body) {
                tbody.innerHTML = '';

                if (body.status !== 'success' || (body.data.publicaciones || []).length === 0) {
                    var tr = document.createElement('tr');
                    var td = document.createElement('td');
                    td.colSpan = 5;
                    td.textContent = body.status === 'success' ? 'Sin publicaciones registradas todavía.' : (body.message || 'No fue posible cargar el historial.');
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                    return;
                }

                body.data.publicaciones.forEach(function (pub) {
                    var tr = document.createElement('tr');

                    var tdPlataforma = document.createElement('td');
                    tdPlataforma.textContent = pub.plataforma;
                    tr.appendChild(tdPlataforma);

                    var tdTexto = document.createElement('td');
                    tdTexto.textContent = (pub.texto || '').slice(0, 60) + ((pub.texto || '').length > 60 ? '…' : '');
                    tr.appendChild(tdTexto);

                    var tdEstado = document.createElement('td');
                    var badge = document.createElement('span');
                    badge.className = 'estado-badge estado-badge--' + pub.estado;
                    badge.textContent = ESTADO_LABELS[pub.estado] || pub.estado;
                    tdEstado.appendChild(badge);
                    tr.appendChild(tdEstado);

                    var tdProgramado = document.createElement('td');
                    tdProgramado.textContent = pub.programado_para || '—';
                    tr.appendChild(tdProgramado);

                    var tdFecha = document.createElement('td');
                    tdFecha.textContent = pub.created_at;
                    tr.appendChild(tdFecha);

                    tbody.appendChild(tr);
                });
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="5">No fue posible conectar con el servidor.</td></tr>';
            });
    }

    function aplicarTransferenciaSiExiste() {
        var raw = sessionStorage.getItem(TRANSFER_KEY);
        if (!raw) {
            return;
        }
        sessionStorage.removeItem(TRANSFER_KEY);

        try {
            var datos = JSON.parse(raw);
            if (datos.texto) {
                document.getElementById('social-texto').value = datos.texto;
            }
            if (datos.plataforma && datos.plataforma !== 'web') {
                document.getElementById('social-plataforma').value = datos.plataforma;
            }
            if (datos.producto_id) {
                document.getElementById('social-producto').value = String(datos.producto_id);
            }
            actualizarPreview();
        } catch (e) {
            // Transferencia corrupta — se ignora silenciosamente, el usuario redacta manualmente.
        }
    }

    function init() {
        var form = document.getElementById('social-form');
        if (!form) {
            return;
        }

        cargarProductos();
        aplicarTransferenciaSiExiste();
        cargarHistorial();
        actualizarPreview();

        document.getElementById('social-texto').addEventListener('input', actualizarPreview);
        document.getElementById('social-media-url').addEventListener('input', actualizarPreview);
        form.addEventListener('submit', manejarEnvio);

        document.getElementById('social-publicar-ahora').addEventListener('change', function (e) {
            document.getElementById('social-programado').disabled = e.target.checked;
        });
    }

    global.PPAdminSocial = { init: init };
})(window, document);
