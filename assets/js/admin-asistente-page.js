// assets/js/admin-asistente-page.js — PinturaPittsburgh_LaPazBCS
// Wiring de admin/asistente.html: guarda de sesión, logout y arranque del módulo.
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

    if (window.PPAdminAsistente) {
        window.PPAdminAsistente.init();
    }
});
