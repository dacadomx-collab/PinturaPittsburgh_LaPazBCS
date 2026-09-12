// assets/js/admin-login-guard.js — PinturaPittsburgh_LaPazBCS
// Guardia inversa de admin/login.php: si YA existe una sesión local con
// access_token, redirige de inmediato por rol en vez de mostrar el
// formulario de nuevo. Se carga SIN "defer", igual que theme-init.js y
// admin-guard.js, ANTES de assets/css/main.css — así la redirección ocurre
// antes de que el usuario vea el formulario, no después de un parpadeo.
//
// Límite honesto (mismo que admin-guard.js, ver CLAUDE.md §14): esto NO es
// una validación de sesión server-side — es Bearer JWT en sessionStorage, y
// esta comprobación solo mira si el token EXISTE, no si el servidor todavía
// lo acepta. Si el token ya expiró, el redirect igual ocurre (optimista);
// la página de destino (admin/index.php u onboarding) hace su propia
// llamada autenticada real con authFetch(), y si el token ya no es válido,
// ese flujo ya existente (admin-auth.js) limpia la sesión y regresa aquí.
(function (document, window) {
    'use strict';

    var STORAGE_KEY = 'pp_admin_session';

    var session = null;
    try {
        session = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
    } catch (e) {
        session = null;
    }

    if (!session || !session.access_token) {
        return;
    }

    if (session.role === 'colaborador') {
        window.location.replace('../Colaboradores/onboarding_colaborador.html');
    } else {
        window.location.replace('index.php');
    }
})(document, window);
