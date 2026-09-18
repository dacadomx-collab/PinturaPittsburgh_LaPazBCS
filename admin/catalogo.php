<?php

declare(strict_types=1);

// =============================================================================
// admin/catalogo.php — Control de Catálogo y Precios (Hito 12/Directiva 2)
// Migrado desde admin/catalogo.html al shell unificado admin/layout/*.php.
// Contenido interno y IDs sin cambios — mismo contrato con admin-catalogo.js
// y admin-catalogo-page.js (Contrato 8).
// =============================================================================

$pageTitle = 'Catálogo y Precios';
$activeNav = 'catalogo';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <h1>Control de Catálogo y Precios</h1>
        <p class="brand-tagline">Actualiza precio y stock por presentación (cuarto de galón, galón, cubeta). Disponibilidad de piso de venta: Mariano Abasolo 3114, Pueblo Nuevo.</p>
        <p id="catalogo-admin-status" class="brand-tagline"></p>

        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Volumen</th>
                        <th>SKU</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="catalogo-admin-tbody"></tbody>
            </table>
        </div>
<?php
$pageScripts = ['../assets/js/admin-catalogo.js', '../assets/js/admin-catalogo-page.js'];
require __DIR__ . '/layout/footer.php';
?>
