// assets/js/admin-login.js — PinturaPittsburgh_LaPazBCS
// Wiring del formulario de admin/login.html. Requiere admin-auth.js cargado antes.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('admin-login-form');
    var statusEl = document.getElementById('login-status');

    if (!form || !window.PPAdminAuth) {
        return;
    }

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
                    window.location.href = '../Colaboradores/onboarding_colaborador.html';
                } else {
                    window.location.href = 'catalogo.html';
                }
            })
            .catch(function (err) {
                statusEl.hidden = false;
                statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                statusEl.textContent = err.message || 'No fue posible iniciar sesión.';
            });
    });
});
