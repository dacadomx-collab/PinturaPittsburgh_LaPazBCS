// assets/js/admin-catalogo.js — PinturaPittsburgh_LaPazBCS
// Panel admin: control de precios/stock de producto_presentaciones.
(function (global, document) {
    'use strict';

    function cargar() {
        var tbody = document.getElementById('catalogo-admin-tbody');
        var statusEl = document.getElementById('catalogo-admin-status');
        if (!tbody) {
            return;
        }

        global.PPAdminAuth.authFetch('../api/admin/catalogo_admin.php', { method: 'GET' })
            .then(function (res) { return res.json(); })
            .then(function (body) {
                if (body.status !== 'success') {
                    statusEl.textContent = body.message || 'No fue posible cargar el catálogo.';
                    return;
                }
                renderFilas(body.data.filas);
                statusEl.textContent = '';
            })
            .catch(function () {
                statusEl.textContent = 'No fue posible conectar con el servidor. Verifica que exista una base de datos configurada (.env).';
            });
    }

    function renderFilas(filas) {
        var tbody = document.getElementById('catalogo-admin-tbody');
        tbody.innerHTML = '';

        if (!filas || filas.length === 0) {
            var tr = document.createElement('tr');
            var td = document.createElement('td');
            td.colSpan = 6;
            td.textContent = 'Sin productos registrados todavía.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        filas.forEach(function (fila) {
            var tr = document.createElement('tr');
            tr.appendChild(celda(fila.nombre));
            tr.appendChild(celda(fila.volumen || '—'));
            tr.appendChild(celda(fila.sku || '—'));
            tr.appendChild(celdaInput('precio', fila.presentacion_id, fila.precio));
            tr.appendChild(celdaInput('stock', fila.presentacion_id, fila.stock));

            var tdAccion = document.createElement('td');
            if (fila.presentacion_id) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn';
                btn.textContent = 'Guardar';
                btn.addEventListener('click', function () { guardar(tr, fila.presentacion_id); });
                tdAccion.appendChild(btn);
            }
            tr.appendChild(tdAccion);

            tbody.appendChild(tr);
        });
    }

    function celda(texto) {
        var td = document.createElement('td');
        td.textContent = texto === null || texto === undefined ? '—' : texto;
        return td;
    }

    function celdaInput(campo, presentacionId, valor) {
        var td = document.createElement('td');
        if (!presentacionId) {
            td.textContent = '—';
            return td;
        }
        var input = document.createElement('input');
        input.type = 'number';
        input.className = 'field';
        input.min = '0';
        input.step = campo === 'precio' ? '0.01' : '1';
        input.value = valor === null || valor === undefined ? '' : valor;
        input.dataset.campo = campo;
        td.appendChild(input);
        return td;
    }

    function guardar(tr, presentacionId) {
        var inputs = tr.querySelectorAll('input[data-campo]');
        var body = { presentacion_id: presentacionId };
        inputs.forEach(function (input) {
            body[input.dataset.campo] = parseFloat(input.value);
        });

        global.PPAdminAuth.authFetch('../api/admin/catalogo_admin.php', {
            method: 'PUT',
            body: JSON.stringify(body)
        })
            .then(function (res) { return res.json(); })
            .then(function (resBody) {
                var statusEl = document.getElementById('catalogo-admin-status');
                statusEl.textContent = resBody.status === 'success'
                    ? 'Presentación actualizada correctamente.'
                    : (resBody.message || 'No fue posible guardar.');
            });
    }

    global.PPAdminCatalogo = { init: cargar };
})(window, document);
