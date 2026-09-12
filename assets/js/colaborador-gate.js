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
// NOTA: no se usa api/status_check.php como verificación de sesión — ese
// endpoint es un chequeo de salud del servidor (filesystem/BD/SMTP), no
// valida tokens JWT. Verificar "sesión válida" contra un endpoint que no
// sabe qué es un JWT sería seguridad de utilería, no seguridad real.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.PPAdminAuth) {
        window.location.href = '../admin/login.html';
        return;
    }

    window.PPAdminAuth.requireSession('../admin/login.html');
});
