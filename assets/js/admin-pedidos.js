// assets/js/admin-pedidos.js — PinturaPittsburgh_LaPazBCS
// Lógica de admin/pedidos.php: consume api/admin/pedidos_listar.php
// (Contrato 13) vía authFetch (Bearer JWT), con filtro de estatus por
// botones. Solo lectura — ver nota de admin/pedidos.php sobre mutaciones.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.PPAdminAuth) {
        return;
    }

    var tbody     = document.getElementById('pedidos-tbody');
    var statusEl  = document.getElementById('pedidos-status');
    var botones   = document.querySelectorAll('[data-filtro-estatus]');

    var ESTATUS_LEGIBLE = {
        pendiente: 'Pendiente',
        confirmado: 'Confirmado',
        en_ruta: 'En ruta',
        entregado: 'Entregado',
        cancelado: 'Cancelado'
    };

    var MODALIDAD_LEGIBLE = {
        entrega_domicilio: 'Entrega a domicilio',
        recoleccion_tienda: 'Recolección en tienda'
    };

    function celda(texto) {
        var td = document.createElement('td');
        td.textContent = texto === null || texto === undefined || texto === '' ? '—' : texto;
        return td;
    }

    function badgeEstatus(estatus) {
        var span = document.createElement('span');
        span.className = 'estado-badge estado-badge--' + estatus;
        span.textContent = ESTATUS_LEGIBLE[estatus] || estatus;
        return span;
    }

    function formatoMoneda(valor) {
        var numero = Number(valor);
        return isNaN(numero) ? String(valor) : '$' + numero.toFixed(2);
    }

    function cargarPedidos(estatus) {
        if (statusEl) { statusEl.textContent = 'Cargando...'; }

        var url = '../api/admin/pedidos_listar.php?limite=50';
        if (estatus) {
            url += '&estatus=' + encodeURIComponent(estatus);
        }

        window.PPAdminAuth.authFetch(url)
            .then(function (res) { return res.json(); })
            .then(function (body) {
                if (body.status !== 'success') {
                    throw new Error(body.message || 'Respuesta no exitosa');
                }
                if (statusEl) { statusEl.textContent = ''; }
                if (!tbody) { return; }

                tbody.innerHTML = '';
                var pedidos = body.data.pedidos || [];

                if (pedidos.length === 0) {
                    var filaVacia = document.createElement('tr');
                    filaVacia.appendChild(celda('No hay pedidos con este filtro.'));
                    tbody.appendChild(filaVacia);
                    return;
                }

                pedidos.forEach(function (pedido) {
                    var fila = document.createElement('tr');
                    fila.appendChild(celda(pedido.cliente_nombre));
                    fila.appendChild(celda(pedido.cliente_telefono));
                    fila.appendChild(celda(MODALIDAD_LEGIBLE[pedido.modalidad] || pedido.modalidad));
                    fila.appendChild(celda(pedido.cp_entrega));

                    var tdEstatus = document.createElement('td');
                    tdEstatus.appendChild(badgeEstatus(pedido.estatus));
                    fila.appendChild(tdEstatus);

                    fila.appendChild(celda(formatoMoneda(pedido.total)));
                    fila.appendChild(celda(pedido.created_at));
                    tbody.appendChild(fila);
                });
            })
            .catch(function () {
                if (statusEl) { statusEl.textContent = 'No fue posible cargar los pedidos. Verifica la conexión con la base de datos.'; }
                if (tbody) {
                    tbody.innerHTML = '';
                    var filaError = document.createElement('tr');
                    filaError.appendChild(celda('Error al cargar pedidos.'));
                    tbody.appendChild(filaError);
                }
            });
    }

    botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
            cargarPedidos(boton.getAttribute('data-filtro-estatus'));
        });
    });

    cargarPedidos('');
});
