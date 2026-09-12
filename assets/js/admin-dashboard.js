// assets/js/admin-dashboard.js — PinturaPittsburgh_LaPazBCS
// Lógica de admin/index.php: consume api/admin/pedidos_listar.php (Contrato 13)
// y api/admin/social_historial.php (Contrato 9) vía authFetch (Bearer JWT) y
// puebla las tarjetas KPI + la tabla de pedidos recientes.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.PPAdminAuth) {
        return;
    }

    var kpiPendientes = document.querySelector('[data-field="kpi-pedidos-pendientes"]');
    var kpiTotal       = document.querySelector('[data-field="kpi-pedidos-total"]');
    var kpiNota        = document.querySelector('[data-field="kpi-pedidos-nota"]');
    var kpiSocialEstado = document.querySelector('[data-field="kpi-social-estado"]');
    var kpiSocialFecha  = document.querySelector('[data-field="kpi-social-fecha"]');
    var tbody           = document.getElementById('admin-dashboard-pedidos-tbody');
    var kpiErrorMsg      = document.querySelector('[data-state="error"]');

    var ESTATUS_LEGIBLE = {
        pendiente: 'Pendiente',
        confirmado: 'Confirmado',
        en_ruta: 'En ruta',
        entregado: 'Entregado',
        cancelado: 'Cancelado'
    };

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

    function celda(texto) {
        var td = document.createElement('td');
        td.textContent = texto;
        return td;
    }

    window.PPAdminAuth.authFetch('../api/admin/pedidos_listar.php?limite=10')
        .then(function (res) { return res.json(); })
        .then(function (body) {
            if (body.status !== 'success') {
                throw new Error(body.message || 'Respuesta no exitosa');
            }
            var datos = body.data;
            if (kpiPendientes) { kpiPendientes.textContent = String(datos.total_pendientes); }
            if (kpiTotal) { kpiTotal.textContent = String(datos.total_general); }
            if (kpiNota) { kpiNota.textContent = datos.total_pendientes === 1 ? 'requiere atención' : 'requieren atención'; }

            if (!tbody) {
                return;
            }
            tbody.innerHTML = '';

            if (datos.pedidos.length === 0) {
                var filaVacia = document.createElement('tr');
                filaVacia.appendChild(celda('Aún no hay pedidos registrados.'));
                tbody.appendChild(filaVacia);
                return;
            }

            datos.pedidos.forEach(function (pedido) {
                var fila = document.createElement('tr');
                fila.appendChild(celda(pedido.cliente_nombre));
                fila.appendChild(celda(pedido.modalidad === 'entrega_domicilio' ? 'Entrega a domicilio' : 'Recolección en tienda'));

                var tdEstatus = document.createElement('td');
                tdEstatus.appendChild(badgeEstatus(pedido.estatus));
                fila.appendChild(tdEstatus);

                fila.appendChild(celda(formatoMoneda(pedido.total)));
                fila.appendChild(celda(pedido.created_at));
                tbody.appendChild(fila);
            });
        })
        .catch(function () {
            if (kpiErrorMsg) { kpiErrorMsg.hidden = false; }
            if (tbody) {
                tbody.innerHTML = '';
                var filaError = document.createElement('tr');
                filaError.appendChild(celda('No fue posible cargar los pedidos recientes.'));
                tbody.appendChild(filaError);
            }
        });

    window.PPAdminAuth.authFetch('../api/admin/social_historial.php')
        .then(function (res) { return res.json(); })
        .then(function (body) {
            if (body.status !== 'success') {
                throw new Error(body.message || 'Respuesta no exitosa');
            }
            var publicaciones = body.data.publicaciones || [];
            if (publicaciones.length === 0) {
                if (kpiSocialEstado) { kpiSocialEstado.textContent = 'Sin publicaciones'; }
                if (kpiSocialFecha) { kpiSocialFecha.textContent = 'Aún no se ha publicado contenido'; }
                return;
            }
            var ultima = publicaciones[0];
            if (kpiSocialEstado) { kpiSocialEstado.textContent = ultima.estado; }
            if (kpiSocialFecha) { kpiSocialFecha.textContent = ultima.publicado_en || ultima.programado_para || ultima.created_at; }
        })
        .catch(function () {
            if (kpiSocialEstado) { kpiSocialEstado.textContent = '—'; }
            if (kpiSocialFecha) { kpiSocialFecha.textContent = 'No disponible'; }
        });
});
