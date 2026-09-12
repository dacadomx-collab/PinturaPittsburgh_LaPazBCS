<?php

declare(strict_types=1);

// =============================================================================
// admin/layout/topbar.php — Barra superior del backoffice (Hito 12/Directiva 2)
// Botón hamburguesa (móvil), perfil activo (poblado en cliente por
// assets/js/admin-topbar.js decodificando el JWT ya emitido), toggle
// Día/Noche, botón de cierre de sesión y botón flotante "Volver Arriba".
// =============================================================================

$pageTitle = $pageTitle ?? 'Panel Administrativo';
?>
<header class="admin-shell__bar">
    <div class="admin-shell__bar-group">
        <button type="button" id="admin-burger-btn" class="admin-burger" aria-label="Abrir menú" aria-expanded="false" aria-controls="admin-sidebar">
            <span class="admin-burger__line"></span>
            <span class="admin-burger__line"></span>
            <span class="admin-burger__line"></span>
        </button>
        <strong><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
    </div>
    <div class="admin-shell__bar-group">
        <span id="admin-profile-chip" class="admin-profile-chip"></span>
        <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" aria-label="Cambiar entre modo día y modo noche"></button>
        <button type="button" id="admin-logout-btn" class="btn btn--gold">Cerrar sesión</button>
    </div>
</header>

<div class="admin-body">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content container">
