// assets/js/admin-catalogo-page.js — PinturaPittsburgh_LaPazBCS
// Wiring de admin/catalogo.php: guarda de sesión, logout y arranque del módulo.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.PPAdminAuth) {
        return;
    }

    window.PPAdminAuth.requireSession();

    var logoutBtn = document.getElementById('admin-logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            window.PPAdminAuth.logout();
        });
    }

    if (window.PPAdminCatalogo) {
        window.PPAdminCatalogo.init();
    }
});
