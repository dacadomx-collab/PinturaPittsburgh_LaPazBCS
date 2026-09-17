<?php

declare(strict_types=1);

// =============================================================================
// admin/login.php — Acceso Administrativo (Hito 14)
// Migrado desde admin/login.html a PHP por consistencia con admin/layout/*.php,
// pero SIN el shell autenticado (sidebar/topbar no aplican antes de iniciar
// sesión) — solo comparte los mismos tokens de CSS y el mismo mecanismo de
// tema que el resto del backoffice.
//
// "Validación server-side de sesión ya existente" (Hito 14, Directiva 1):
// este proyecto usa Bearer JWT en sessionStorage (Hito 2), no cookies/sesión
// PHP — PHP no puede leer sessionStorage. El equivalente honesto es un guard
// de cliente que se ejecuta ANTES del primer paint (assets/js/admin-login-guard.js,
// mismo patrón anti-parpadeo que theme-init.js/admin-guard.js): si ya hay una
// sesión local, redirige de inmediato por rol sin llegar a mostrar el
// formulario. Ver razonamiento completo en CLAUDE.md §14.
// =============================================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo — Famza</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="../favicon.ico">
    <script src="../assets/js/theme-init.js"></script>
    <script src="../assets/js/admin-login-guard.js"></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="site-header">
        <div class="container site-header__bar">
            <p class="site-header__value-prop">Famza — Panel Administrativo</p>
            <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" aria-label="Cambiar entre modo día y modo noche"></button>
        </div>
    </header>

    <main class="container arf-grid">
        <section class="card arf-col-2" aria-labelledby="login-titulo">
            <h1 id="login-titulo">Iniciar sesión</h1>
            <form id="admin-login-form" class="calculator-grid">
                <div class="field-group">
                    <label for="login-email">Correo electrónico</label>
                    <input class="field" type="email" id="login-email" name="email" required autocomplete="username">
                </div>
                <div class="field-group">
                    <label for="login-password">Contraseña</label>
                    <div class="password-field">
                        <input class="field" type="password" id="login-password" name="password" required autocomplete="current-password">
                        <button type="button" id="login-password-toggle" class="password-field__toggle" aria-pressed="false" aria-controls="login-password">Mostrar</button>
                    </div>
                </div>
                <div class="field-group">
                    <button type="submit" class="btn">Entrar</button>
                </div>
            </form>
            <p id="login-status" class="postal-bar__result" hidden></p>
        </section>
    </main>

    <button type="button" id="back-to-top-btn" class="back-to-top-btn" hidden aria-label="Volver arriba">↑</button>

    <script src="../assets/js/admin-auth.js" defer></script>
    <script src="../assets/js/admin-login.js" defer></script>
    <script src="../assets/js/theme-toggle.js" defer></script>
    <script src="../assets/js/back-to-top.js" defer></script>
</body>
</html>
