// assets/js/admin-usuarios.js — PinturaPittsburgh_LaPazBCS
// Lógica de admin/usuarios.php: consume api/admin/usuarios_admin.php
// (Contrato 15) vía authFetch (Bearer JWT). El admin autenticado nunca ve
// controles de acción en su propia fila — el servidor ya lo rechaza (403),
// esto solo evita que intente algo que sabemos que va a fallar.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.PPAdminAuth) {
        return;
    }

    var tbody      = document.getElementById('usuarios-tbody');
    var statusEl   = document.getElementById('usuarios-status');
    var revealEl   = document.getElementById('usuarios-password-reveal');

    var ROLES = ['admin', 'staff', 'colaborador'];
    var ROL_LEGIBLE = { admin: 'Admin', staff: 'Staff', colaborador: 'Colaborador' };

    function idPropio() {
        var sesion = window.PPAdminAuth.getSession();
        if (!sesion) { return null; }
        var claims = window.PPAdminAuth.decodeJwtPayload(sesion.access_token);
        return claims ? claims.sub : null;
    }

    function mostrarEstado(mensaje, tipo) {
        if (!statusEl) { return; }
        statusEl.hidden = !mensaje;
        statusEl.textContent = mensaje || '';
        statusEl.className = 'postal-bar__result' + (tipo ? ' postal-bar__result--' + tipo : '');
    }

    function ocultarPasswordRevelada() {
        if (revealEl) { revealEl.hidden = true; }
    }

    function celda(texto) {
        var td = document.createElement('td');
        td.textContent = texto === null || texto === undefined || texto === '' ? '—' : texto;
        return td;
    }

    function badgeEstatus(estatus) {
        var span = document.createElement('span');
        span.className = 'estado-badge estado-badge--' + estatus;
        span.textContent = estatus === 'activo' ? 'Activo' : 'Inactivo';
        return span;
    }

    function mutar(body) {
        return window.PPAdminAuth.authFetch('../api/admin/usuarios_admin.php', {
            method: 'PUT',
            body: JSON.stringify(body)
        }).then(function (res) { return res.json(); });
    }

    function cargarUsuarios() {
        mostrarEstado('Cargando usuarios...', '');
        ocultarPasswordRevelada();

        window.PPAdminAuth.authFetch('../api/admin/usuarios_admin.php')
            .then(function (res) { return res.json(); })
            .then(function (body) {
                if (body.status !== 'success') {
                    throw new Error(body.message || 'Respuesta no exitosa');
                }
                mostrarEstado('', '');
                renderTabla(body.data.usuarios || []);
            })
            .catch(function () {
                mostrarEstado('No fue posible cargar los usuarios. Verifica la conexión con la base de datos.', 'blocked');
                if (tbody) {
                    tbody.innerHTML = '';
                    tbody.appendChild(document.createElement('tr'));
                }
            });
    }

    function renderTabla(usuarios) {
        if (!tbody) { return; }
        tbody.innerHTML = '';

        if (usuarios.length === 0) {
            var filaVacia = document.createElement('tr');
            filaVacia.appendChild(celda('No hay usuarios registrados.'));
            tbody.appendChild(filaVacia);
            return;
        }

        var miId = idPropio();

        usuarios.forEach(function (usuario) {
            var esPropio = miId !== null && Number(miId) === Number(usuario.id);
            var fila = document.createElement('tr');

            fila.appendChild(celda(usuario.email));

            // ── Rol: select + botón Guardar ──────────────────────────────────
            var tdRol = document.createElement('td');
            if (esPropio) {
                tdRol.appendChild(document.createTextNode(ROL_LEGIBLE[usuario.role] || usuario.role));
            } else {
                var select = document.createElement('select');
                select.className = 'field';
                ROLES.forEach(function (rol) {
                    var opt = document.createElement('option');
                    opt.value = rol;
                    opt.textContent = ROL_LEGIBLE[rol];
                    if (rol === usuario.role) { opt.selected = true; }
                    select.appendChild(opt);
                });
                var btnRol = document.createElement('button');
                btnRol.type = 'button';
                btnRol.className = 'btn admin-row-actions__btn';
                btnRol.textContent = 'Guardar rol';
                btnRol.addEventListener('click', function () {
                    if (select.value === usuario.role) { return; }
                    mostrarEstado('Actualizando rol...', '');
                    mutar({ action: 'cambiar_rol', id: usuario.id, role: select.value })
                        .then(function (resBody) {
                            if (resBody.status !== 'success') { throw new Error(resBody.message); }
                            mostrarEstado('Rol actualizado. El usuario deberá iniciar sesión de nuevo.', 'ok');
                            cargarUsuarios();
                        })
                        .catch(function (err) {
                            mostrarEstado(err.message || 'No fue posible actualizar el rol.', 'blocked');
                        });
                });
                tdRol.appendChild(select);
                tdRol.appendChild(btnRol);
            }
            fila.appendChild(tdRol);

            // ── Estatus: badge + botón Suspender/Activar ─────────────────────
            var tdEstatus = document.createElement('td');
            tdEstatus.appendChild(badgeEstatus(usuario.estatus));
            if (usuario.bloqueado) {
                var badgeBloqueo = document.createElement('span');
                badgeBloqueo.className = 'estado-badge estado-badge--cancelado';
                badgeBloqueo.textContent = 'Bloqueado (rate limit)';
                tdEstatus.appendChild(document.createTextNode(' '));
                tdEstatus.appendChild(badgeBloqueo);
            }
            if (!esPropio) {
                var nuevoEstatus = usuario.estatus === 'activo' ? 'inactivo' : 'activo';
                var btnEstatus = document.createElement('button');
                btnEstatus.type = 'button';
                btnEstatus.className = 'btn admin-row-actions__btn';
                btnEstatus.textContent = usuario.estatus === 'activo' ? 'Suspender' : 'Activar';
                btnEstatus.addEventListener('click', function () {
                    var confirmacion = usuario.estatus === 'activo'
                        ? '¿Suspender a ' + usuario.email + '? Perderá acceso de inmediato.'
                        : '¿Reactivar a ' + usuario.email + '?';
                    if (!window.confirm(confirmacion)) { return; }

                    mostrarEstado('Actualizando estatus...', '');
                    mutar({ action: 'cambiar_estatus', id: usuario.id, estatus: nuevoEstatus })
                        .then(function (resBody) {
                            if (resBody.status !== 'success') { throw new Error(resBody.message); }
                            mostrarEstado('Estatus actualizado.', 'ok');
                            cargarUsuarios();
                        })
                        .catch(function (err) {
                            mostrarEstado(err.message || 'No fue posible actualizar el estatus.', 'blocked');
                        });
                });
                tdEstatus.appendChild(document.createElement('br'));
                tdEstatus.appendChild(btnEstatus);
            }
            fila.appendChild(tdEstatus);

            fila.appendChild(celda(usuario.created_at));

            // ── Acciones: resetear contraseña ────────────────────────────────
            var tdAcciones = document.createElement('td');
            if (esPropio) {
                tdAcciones.appendChild(document.createTextNode('Tu cuenta'));
            } else {
                var btnReset = document.createElement('button');
                btnReset.type = 'button';
                btnReset.className = 'btn admin-row-actions__btn';
                btnReset.textContent = 'Resetear contraseña';
                btnReset.addEventListener('click', function () {
                    if (!window.confirm('¿Generar una contraseña nueva para ' + usuario.email + '? Su sesión actual se cerrará.')) {
                        return;
                    }
                    mostrarEstado('Generando contraseña...', '');
                    mutar({ action: 'resetear_password', id: usuario.id })
                        .then(function (resBody) {
                            if (resBody.status !== 'success') { throw new Error(resBody.message); }
                            mostrarEstado('', '');
                            if (revealEl) {
                                revealEl.hidden = false;
                                revealEl.textContent = 'Nueva contraseña para ' + usuario.email + ': '
                                    + resBody.data.password_temporal + ' — cópiala ahora, no se volverá a mostrar.';
                            }
                        })
                        .catch(function (err) {
                            mostrarEstado(err.message || 'No fue posible resetear la contraseña.', 'blocked');
                        });
                });
                tdAcciones.appendChild(btnReset);
            }
            fila.appendChild(tdAcciones);

            tbody.appendChild(fila);
        });
    }

    cargarUsuarios();
});
