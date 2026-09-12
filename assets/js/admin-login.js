// assets/js/admin-login.js — PinturaPittsburgh_LaPazBCS
// Wiring del formulario de admin/login.php. Requiere admin-auth.js cargado antes.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('admin-login-form');
    var statusEl = document.getElementById('login-status');

    if (!form || !window.PPAdminAuth) {
        return;
    }

    // Ruta de onboarding por colaborador (Hito 19 — Moy se suma a Rafael).
    // El email viene del JWT ya emitido por el servidor (claim, no se decide
    // en el cliente) — solo se usa para escoger A CUÁL onboarding enrutar,
    // nunca para decidir si la sesión es válida (eso ya lo validó el server).
    var RUTA_ONBOARDING_POR_EMAIL = {
        'armandocastillejos086@gmail.com': '../Colaboradores/onboarding_colaborador.html',
        'mescobar_22@alu.uabcs.mx': '../Colaboradores/onboarding_moy.html'
    };
    var RUTA_ONBOARDING_DEFECTO = '../Colaboradores/onboarding_colaborador.html';

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var email = document.getElementById('login-email').value;
        var password = document.getElementById('login-password').value;

        window.PPAdminAuth.login(email, password)
            .then(function (sesion) {
                // Blindaje de Login por Rol (Directiva Zero-Trust): el rol
                // viene del JWT ya emitido por api/auth_login.php — nunca se
                // decide en el cliente, solo se enruta según lo que el
                // servidor ya autenticó.
                if (sesion.role === 'colaborador') {
                    var claims = window.PPAdminAuth.decodeJwtPayload(sesion.access_token);
                    var emailToken = claims && claims.email;
                    window.location.href = RUTA_ONBOARDING_POR_EMAIL[emailToken] || RUTA_ONBOARDING_DEFECTO;
                } else {
                    window.location.href = 'index.php';
                }
            })
            .catch(function (err) {
                statusEl.hidden = false;
                statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                statusEl.textContent = err.message || 'No fue posible iniciar sesión.';
            });
    });
});
