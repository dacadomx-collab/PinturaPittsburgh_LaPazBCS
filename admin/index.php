<?php

declare(strict_types=1);

// =============================================================================
// admin/index.php — Panel Central del Backoffice (Hito 12/Directiva 2)
// Jerarquía de widgets (MODULO_01_LOGIN_Y_ACCESO.md §5.3.3, adoptado como hoja
// de ruta — ver CLAUDE.md §14): Acción Rápida → KPIs → Historial. Todos los
// datos llegan vía authFetch() a endpoints ya existentes (Contrato 9 y el
// nuevo Contrato 13) — este archivo no imprime ningún dato real en el HTML.
// =============================================================================

$pageTitle = 'Inicio';
$activeNav = 'inicio';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <h1>Bienvenido al Panel Administrativo</h1>
        <p class="brand-tagline">PinturaPittsburgh — Distribuidor Autorizado en La Paz, B.C.S. Resumen operativo del día.</p>

        <section aria-labelledby="admin-accion-rapida-titulo">
            <h2 id="admin-accion-rapida-titulo" class="text-center">Acceso Rápido</h2>
            <div class="admin-quick-links">
                <a class="btn admin-quick-links__item" href="catalogo.php">🎨 Catálogo y Precios</a>
                <a class="btn admin-quick-links__item" href="pedidos.php">📦 Pedidos</a>
                <a class="btn admin-quick-links__item" href="social.php">📣 Publicador Social</a>
                <a class="btn admin-quick-links__item" href="asistente.php">🤖 Asistente IA</a>
                <a class="btn admin-quick-links__item" href="usuarios.php">👥 Usuarios</a>
                <a class="btn admin-quick-links__item" href="auditoria.php">🛡️ Auditoría</a>
            </div>
        </section>

        <section aria-labelledby="admin-kpi-titulo">
            <h2 id="admin-kpi-titulo" class="text-center">Estado Operativo</h2>
            <div class="admin-kpi-grid" data-state="loading">
                <div class="admin-kpi-card">
                    <p class="admin-kpi-card__label">Pedidos pendientes</p>
                    <p class="admin-kpi-card__value" data-field="kpi-pedidos-pendientes">—</p>
                    <p class="admin-kpi-card__note" data-field="kpi-pedidos-nota">Cargando...</p>
                </div>
                <div class="admin-kpi-card">
                    <p class="admin-kpi-card__label">Pedidos totales</p>
                    <p class="admin-kpi-card__value" data-field="kpi-pedidos-total">—</p>
                    <p class="admin-kpi-card__note">Histórico completo</p>
                </div>
                <div class="admin-kpi-card">
                    <p class="admin-kpi-card__label">Última publicación social</p>
                    <p class="admin-kpi-card__value" data-field="kpi-social-estado">—</p>
                    <p class="admin-kpi-card__note" data-field="kpi-social-fecha">Cargando...</p>
                </div>
            </div>
            <p class="brand-tagline" data-state="error" hidden>No fue posible cargar el estado operativo. Verifica la conexión con la base de datos (ver <code>api/status_check.php</code>).</p>
        </section>

        <section aria-labelledby="admin-historial-titulo">
            <h2 id="admin-historial-titulo" class="text-center">Pedidos Recientes</h2>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Modalidad</th>
                            <th>Estatus</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="admin-dashboard-pedidos-tbody">
                        <tr><td colspan="5">Cargando pedidos recientes...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
<?php
$pageScripts = ['../assets/js/admin-dashboard.js'];
require __DIR__ . '/layout/footer.php';
?>
