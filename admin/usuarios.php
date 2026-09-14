<?php

declare(strict_types=1);

// =============================================================================
// admin/usuarios.php — Controlador Central de Usuarios (Hito 23, Módulo 01 §9.5)
// Consume api/admin/usuarios_admin.php (Contrato 15). Cambiar rol, suspender/
// activar y resetear contraseña — el admin autenticado nunca puede mutar su
// propia cuenta (regla de auto-protección aplicada server-side).
// =============================================================================

$pageTitle = 'Usuarios';
$activeNav = 'usuarios';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <h1>Usuarios</h1>
        <p class="brand-tagline">Cuentas del backoffice — admin, staff y colaboradores externos. No puedes modificar tu propia cuenta desde este panel.</p>

        <p id="usuarios-status" class="postal-bar__result" hidden></p>
        <p id="usuarios-password-reveal" class="postal-bar__result postal-bar__result--warning" hidden></p>

        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estatus</th>
                        <th>Alta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuarios-tbody">
                    <tr><td colspan="5">Cargando usuarios...</td></tr>
                </tbody>
            </table>
        </div>
<?php
$pageScripts = ['../assets/js/admin-usuarios.js'];
require __DIR__ . '/layout/footer.php';
?>
