// assets/js/back-to-top.js — PinturaPittsburgh_LaPazBCS
// Botón flotante de retorno superior — aparece tras 400px de scroll,
// desaparece cerca del tope. No altera el flujo del DOM (position: fixed).
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('back-to-top-btn');
    if (!btn) {
        return;
    }

    var UMBRAL_PX = 400;

    function actualizarVisibilidad() {
        btn.hidden = window.scrollY <= UMBRAL_PX;
    }

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', actualizarVisibilidad, { passive: true });
    actualizarVisibilidad();
});
