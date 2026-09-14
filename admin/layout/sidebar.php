<?php

declare(strict_types=1);

// =============================================================================
// admin/layout/sidebar.php — Menú colapsable del backoffice (Hito 12/Directiva 2)
// Off-canvas en móvil (toggle vía admin-burger-btn en topbar.php), fijo en
// escritorio (≥900px, ver assets/css/admin.css). Requiere $activeNav definido
// por la página que hace el require (ver admin/layout/header.php).
// =============================================================================

$activeNav = $activeNav ?? '';

function admin_nav_clase(string $clave, string $activo): string
{
    return $clave === $activo ? 'admin-sidebar__link admin-sidebar__link--active' : 'admin-sidebar__link';
}
?>
<div class="admin-nav-backdrop" data-admin-nav-backdrop></div>

<nav class="admin-sidebar" data-admin-sidebar aria-label="Menú principal del panel administrativo">
    <ul class="admin-sidebar__list">
        <li>
            <a href="index.php" class="<?php echo admin_nav_clase('inicio', $activeNav); ?>">🏠 Inicio</a>
        </li>
        <li>
            <a href="catalogo.php" class="<?php echo admin_nav_clase('catalogo', $activeNav); ?>">🎨 Catálogo</a>
        </li>
        <li>
            <a href="pedidos.php" class="<?php echo admin_nav_clase('pedidos', $activeNav); ?>">📦 Pedidos</a>
        </li>
        <li>
            <a href="social.php" class="<?php echo admin_nav_clase('social', $activeNav); ?>">📣 Publicador Social</a>
        </li>
        <li>
            <a href="asistente.php" class="<?php echo admin_nav_clase('asistente', $activeNav); ?>">🤖 Asistente IA</a>
        </li>
        <li>
            <a href="usuarios.php" class="<?php echo admin_nav_clase('usuarios', $activeNav); ?>">👥 Usuarios</a>
        </li>
        <li>
            <a href="auditoria.php" class="<?php echo admin_nav_clase('auditoria', $activeNav); ?>">🛡️ Auditoría</a>
        </li>
        <li>
            <span class="admin-sidebar__link" aria-disabled="true" title="Próximamente — panel visual para la política de contraseña (api/configuracion_seguridad.php ya existe, sin UI dedicada todavía — ver modulos/MODULO_01_LOGIN_Y_ACCESO.md §7.3)">⚙️ Configuración</span>
        </li>
    </ul>
</nav>
