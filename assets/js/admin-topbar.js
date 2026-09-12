// assets/js/admin-topbar.js — PinturaPittsburgh_LaPazBCS
// Wiring del shell unificado del backoffice (admin/layout/*.php): botón
// hamburguesa (sidebar off-canvas en móvil), backdrop, cierre con Escape,
// chip de perfil activo (decodifica el JWT ya emitido — solo para mostrar
// email/rol, la validación real siempre ocurre en el servidor) y logout.
document.addEventListener('DOMContentLoaded', function () {
    var shell    = document.querySelector('[data-admin-shell]');
    var burger   = document.getElementById('admin-burger-btn');
    var backdrop = document.querySelector('[data-admin-nav-backdrop]');
    var logoutBtn   = document.getElementById('admin-logout-btn');
    var profileChip = document.getElementById('admin-profile-chip');

    function cerrarNav() {
        if (shell) {
            shell.classList.remove('admin-shell--nav-open');
        }
        if (burger) {
            burger.setAttribute('aria-expanded', 'false');
        }
    }

    if (burger && shell) {
        burger.addEventListener('click', function () {
            var abierto = shell.classList.toggle('admin-shell--nav-open');
            burger.setAttribute('aria-expanded', String(abierto));
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', cerrarNav);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            cerrarNav();
        }
    });

    if (logoutBtn && window.PPAdminAuth) {
        logoutBtn.addEventListener('click', function () {
            window.PPAdminAuth.logout();
        });
    }

    if (profileChip && window.PPAdminAuth) {
        var session = window.PPAdminAuth.getSession();
        // decodeJwtPayload centralizado en admin-auth.js desde el Hito 19
        // (antes había una copia de esta misma función en cada archivo).
        var claims = session && session.access_token ? window.PPAdminAuth.decodeJwtPayload(session.access_token) : null;

        if (claims && claims.email) {
            profileChip.textContent = claims.email + ' · ' + (session.role || claims.role || '');
        } else if (session && session.role) {
            profileChip.textContent = session.role;
        }
    }
});
