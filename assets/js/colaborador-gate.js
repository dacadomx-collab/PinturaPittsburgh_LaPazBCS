// assets/js/colaborador-gate.js — PinturaPittsburgh_LaPazBCS
// Guarda de acceso REAL para Colaboradores/onboarding_colaborador.html —
// reemplaza el modal de contraseña falso (checkGate()/'pittsburgh2026', que
// solo vivía en JS del navegador y no protegía nada realmente).
//
// Reutiliza la sesión ya emitida por api/auth_login.php (misma mecánica que
// admin/*.html) en vez de inventar una segunda clave de sessionStorage
// paralela — un solo mecanismo de sesión en todo el proyecto (Mandamiento
// #10: un solo nombre/mecanismo válido por concepto).
//
// Verifica DOS condiciones: (1) existe una sesión con access_token — si no,
// a login; (2) el rol de esa sesión es 'colaborador' — si es admin/staff,
// también se redirige (esta página es exclusiva del rol colaborador; un
// admin ya tiene su propio flujo en admin/index.php).
//
// NOTA: no se usa api/status_check.php como verificación de sesión — ese
// endpoint es un chequeo de salud del servidor (filesystem/BD/SMTP), no
// valida tokens JWT. Verificar "sesión válida" contra un endpoint que no
// sabe qué es un JWT sería seguridad de utilería, no seguridad real.
document.addEventListener('DOMContentLoaded', function () {
    var LOGIN_URL = '../admin/login.html';

    if (!window.PPAdminAuth) {
        window.location.href = LOGIN_URL;
        return;
    }

    var session = window.PPAdminAuth.getSession();

    if (!session || !session.access_token) {
        window.location.href = LOGIN_URL;
        return;
    }

    if (session.role !== 'colaborador') {
        window.location.href = LOGIN_URL;
    }
});
