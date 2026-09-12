// assets/js/admin-guard.js — PinturaPittsburgh_LaPazBCS
// Guardia anti-parpadeo del backoffice: se carga SIN "defer", igual que
// theme-init.js, ANTES de assets/css/admin.css — así la regla
// "html.admin-auth-pending body { visibility: hidden; }" ya está activa por
// el tiempo en que el navegador pinta el primer frame.
//
// Límite honesto de esta técnica (documentado en CLAUDE.md §14 y
// knowledge/04_ARQUITECTURA_Y_BLINDAJE.md): esto NO es una sesión PHP
// blindada server-side — es Bearer JWT en sessionStorage (Hito 2), y ninguna
// de las páginas admin/*.php embebe datos reales en el HTML inicial; todo
// dato sensible llega después vía admin-auth.js#authFetch() con el
// Authorization: Bearer real, que el servidor sí valida (api/auth_middleware.php).
// Este guard solo evita el parpadeo visual de la ESTRUCTURA (chrome) del
// dashboard antes de confirmar que existe una sesión local — la autorización
// real de cada dato ocurre en el backend, nunca aquí.
(function (document, window) {
    'use strict';

    var STORAGE_KEY = 'pp_admin_session';
    var root = document.documentElement;

    root.classList.add('admin-auth-pending');

    if (/\/admin\/login\.html$/.test(window.location.pathname)) {
        root.classList.remove('admin-auth-pending');
        return;
    }

    var session = null;
    try {
        session = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
    } catch (e) {
        session = null;
    }

    if (!session || !session.access_token) {
        window.location.replace('login.html');
        return;
    }

    root.classList.remove('admin-auth-pending');
})(document, window);
