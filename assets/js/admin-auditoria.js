// assets/js/admin-auditoria.js — PinturaPittsburgh_LaPazBCS
// Lógica de admin/auditoria.php: consume api/admin/auditoria_listar.php
// (Contrato 16) vía authFetch (Bearer JWT). Solo lectura — log_actividad es
// append-only, no hay mutaciones posibles desde esta vista.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.PPAdminAuth) {
        return;
    }

    var form        = document.getElementById('auditoria-filtros');
    var eventoSel    = document.getElementById('auditoria-evento');
    var desdeInput   = document.getElementById('auditoria-desde');
    var hastaInput   = document.getElementById('auditoria-hasta');
    var tbody        = document.getElementById('auditoria-tbody');
    var statusEl     = document.getElementById('auditoria-status');
    var btnAnterior  = document.getElementById('auditoria-anterior');
    var btnSiguiente = document.getElementById('auditoria-siguiente');
    var paginaInfo   = document.getElementById('auditoria-pagina-info');

    var EVENTO_LEGIBLE = {
        login_exitoso: 'Login exitoso',
        login_fallido: 'Login fallido',
        cuenta_bloqueada: 'Cuenta bloqueada',
        sesion_invalidada: 'Sesión invalidada'
    };

    var paginaActual = 1;
    var totalPaginas = 1;

    function celda(texto) {
        var td = document.createElement('td');
        td.textContent = texto === null || texto === undefined || texto === '' ? '—' : texto;
        return td;
    }

    function truncarHash(hash) {
        if (!hash) { return '—'; }
        return hash.substring(0, 12) + '…';
    }

    function badgeEvento(evento) {
        var span = document.createElement('span');
        var variante = evento === 'login_exitoso' ? 'entregado'
            : (evento === 'login_fallido' ? 'pendiente'
                : (evento === 'cuenta_bloqueada' ? 'cancelado' : 'en_ruta'));
        span.className = 'estado-badge estado-badge--' + variante;
        span.textContent = EVENTO_LEGIBLE[evento] || evento;
        return span;
    }

    function cargar(pagina) {
        paginaActual = pagina || 1;
        if (statusEl) { statusEl.hidden = false; statusEl.textContent = 'Cargando...'; statusEl.className = 'postal-bar__result'; }

        var params = ['pagina=' + paginaActual, 'limite=20'];
        if (eventoSel.value) { params.push('evento=' + encodeURIComponent(eventoSel.value)); }
        if (desdeInput.value) { params.push('fecha_desde=' + encodeURIComponent(desdeInput.value)); }
        if (hastaInput.value) { params.push('fecha_hasta=' + encodeURIComponent(hastaInput.value)); }

        window.PPAdminAuth.authFetch('../api/admin/auditoria_listar.php?' + params.join('&'))
            .then(function (res) { return res.json(); })
            .then(function (body) {
                if (body.status !== 'success') {
                    throw new Error(body.message || 'Respuesta no exitosa');
                }
                if (statusEl) { statusEl.hidden = true; }
                renderTabla(body.data.registros || []);
                totalPaginas = body.data.total_paginas || 1;
                actualizarPaginacion(body.data.total || 0);
            })
            .catch(function () {
                if (statusEl) {
                    statusEl.hidden = false;
                    statusEl.textContent = 'No fue posible cargar la bitácora. Verifica la conexión con la base de datos.';
                    statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                }
                if (tbody) { tbody.innerHTML = ''; }
            });
    }

    function renderTabla(registros) {
        if (!tbody) { return; }
        tbody.innerHTML = '';

        if (registros.length === 0) {
            var filaVacia = document.createElement('tr');
            filaVacia.appendChild(celda('No hay registros con este filtro.'));
            tbody.appendChild(filaVacia);
            return;
        }

        registros.forEach(function (registro) {
            var fila = document.createElement('tr');
            fila.appendChild(celda(registro.creado_en));

            var tdEvento = document.createElement('td');
            tdEvento.appendChild(badgeEvento(registro.evento));
            fila.appendChild(tdEvento);

            fila.appendChild(celda(registro.usuario_email));
            fila.appendChild(celda(truncarHash(registro.ip_hash)));
            fila.appendChild(celda(truncarHash(registro.device_hash)));
            fila.appendChild(celda(registro.detalle));
            tbody.appendChild(fila);
        });
    }

    function actualizarPaginacion(total) {
        if (paginaInfo) {
            paginaInfo.textContent = 'Página ' + paginaActual + ' de ' + totalPaginas + ' (' + total + ' registros)';
        }
        if (btnAnterior) { btnAnterior.disabled = paginaActual <= 1; }
        if (btnSiguiente) { btnSiguiente.disabled = paginaActual >= totalPaginas; }
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            cargar(1);
        });
    }
    if (btnAnterior) {
        btnAnterior.addEventListener('click', function () {
            if (paginaActual > 1) { cargar(paginaActual - 1); }
        });
    }
    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', function () {
            if (paginaActual < totalPaginas) { cargar(paginaActual + 1); }
        });
    }

    cargar(1);
});
