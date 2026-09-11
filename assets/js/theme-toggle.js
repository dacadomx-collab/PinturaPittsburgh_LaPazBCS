// assets/js/theme-toggle.js — PinturaPittsburgh_LaPazBCS
// Wiring del botón de toggle Día/Noche. Requiere assets/js/theme-init.js
// cargado antes (expone window.PPTheme).
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle-btn');
    if (!btn || !window.PPTheme) {
        return;
    }

    function actualizarEtiqueta() {
        var stored = window.PPTheme.get();
        var sistemaPrefiereOscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var esOscuroActual = stored ? stored === 'dark' : sistemaPrefiereOscuro;

        btn.textContent = esOscuroActual ? '☀️ Modo Día' : '🌙 Modo Noche';
        btn.setAttribute('aria-pressed', String(esOscuroActual));
    }

    btn.addEventListener('click', function () {
        window.PPTheme.toggle();
        actualizarEtiqueta();
    });

    actualizarEtiqueta();
});
