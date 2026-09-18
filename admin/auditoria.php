<?php

declare(strict_types=1);

// =============================================================================
// admin/auditoria.php — Visor de Bitácora de Accesos (Hito 23, Módulo 01 §9.3/§9.6)
// Consume api/admin/auditoria_listar.php (Contrato 16) — tabla paginada
// server-side, filtros por evento y rango de fechas. `log_actividad` es
// append-only (Hito 22): esta vista es exclusivamente de lectura.
// =============================================================================

$pageTitle = 'Auditoría';
$activeNav = 'auditoria';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <h1>Bitácora de Accesos</h1>
        <p class="brand-tagline">Registro append-only de inicios de sesión — nunca se muestra la IP en claro, solo su huella SHA-256.</p>

        <form id="auditoria-filtros" class="calculator-grid">
            <div class="field-group">
                <label for="auditoria-evento">Evento</label>
                <select id="auditoria-evento" class="field">
                    <option value="">Todos</option>
                    <option value="login_exitoso">Login exitoso</option>
                    <option value="login_fallido">Login fallido</option>
                    <option value="cuenta_bloqueada">Cuenta bloqueada</option>
                    <option value="sesion_invalidada">Sesión invalidada</option>
                </select>
            </div>
            <div class="field-group">
                <label for="auditoria-desde">Desde</label>
                <input type="date" id="auditoria-desde" class="field">
            </div>
            <div class="field-group">
                <label for="auditoria-hasta">Hasta</label>
                <input type="date" id="auditoria-hasta" class="field">
            </div>
            <div class="field-group">
                <button type="submit" class="btn">Filtrar</button>
            </div>
        </form>

        <p id="auditoria-status" class="postal-bar__result" hidden></p>

        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Evento</th>
                        <th>Usuario</th>
                        <th>IP (hash)</th>
                        <th>Dispositivo (hash)</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody id="auditoria-tbody">
                    <tr><td colspan="6">Cargando bitácora...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="admin-quick-links" role="group" aria-label="Paginación">
            <button type="button" id="auditoria-anterior" class="btn admin-quick-links__item">← Anterior</button>
            <span id="auditoria-pagina-info" class="brand-tagline"></span>
            <button type="button" id="auditoria-siguiente" class="btn admin-quick-links__item">Siguiente →</button>
        </div>
<?php
$pageScripts = ['../assets/js/admin-auditoria.js'];
require __DIR__ . '/layout/footer.php';
?>
