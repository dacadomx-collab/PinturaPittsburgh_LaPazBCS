// assets/js/theme-init.js — PinturaPittsburgh_LaPazBCS
// Aplica el tema guardado ANTES del primer render (por eso este <script> se
// carga SIN "defer" y antes que cualquier otro JS — evita el parpadeo de
// tema incorrecto al cargar la página). No usa inline <script> en el HTML
// para respetar la Regla de Oro (todo JS vive en assets/js/).
(function (global, document) {
    'use strict';

    var STORAGE_KEY = 'pp_theme';

    function getStoredTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function applyTheme(theme) {
        var root = document.documentElement;
        if (theme === 'dark' || theme === 'light') {
            root.setAttribute('data-theme', theme);
        } else {
            root.removeAttribute('data-theme');
        }
    }

    applyTheme(getStoredTheme());

    global.PPTheme = {
        get: getStoredTheme,
        set: function (theme) {
            try {
                if (theme === 'dark' || theme === 'light') {
                    localStorage.setItem(STORAGE_KEY, theme);
                } else {
                    localStorage.removeItem(STORAGE_KEY);
                }
            } catch (e) {
                // localStorage no disponible (modo privado, etc.) — el tema
                // se aplica igual para esta carga, simplemente no persiste.
            }
            applyTheme(theme);
        },
        toggle: function () {
            var stored = getStoredTheme();
            var sistemaPrefiereOscuro = global.matchMedia && global.matchMedia('(prefers-color-scheme: dark)').matches;
            var esOscuroActual = stored ? stored === 'dark' : sistemaPrefiereOscuro;
            global.PPTheme.set(esOscuroActual ? 'light' : 'dark');
        }
    };
})(window, document);
