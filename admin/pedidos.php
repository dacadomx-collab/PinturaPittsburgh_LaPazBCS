<?php

declare(strict_types=1);

// =============================================================================
// admin/pedidos.php — Gestión de Pedidos (Hito 12/Directiva 2, nueva página)
// Consume api/admin/pedidos_listar.php (Contrato 13). Solo lectura por ahora
// — cambiar el estatus de un pedido no fue solicitado en esta directiva y
// requeriría un nuevo endpoint PUT con su propio contrato (Mandamiento #6,
// Ejecución Determinística: no se agregan mutaciones no pedidas).
// =============================================================================

$pageTitle = 'Pedidos';
$activeNav = 'pedidos';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <h1>Pedidos</h1>
        <p class="brand-tagline">Entrega a domicilio y recolección en tienda — Blvd. Agustín Olachea e Indeco, La Paz, B.C.S.</p>

        <div class="admin-quick-links" role="group" aria-label="Filtrar por estatus">
            <button type="button" class="btn admin-quick-links__item" data-filtro-estatus="">Todos</button>
            <button type="button" class="btn admin-quick-links__item" data-filtro-estatus="pendiente">Pendientes</button>
            <button type="button" class="btn admin-quick-links__item" data-filtro-estatus="confirmado">Confirmados</button>
            <button type="button" class="btn admin-quick-links__item" data-filtro-estatus="en_ruta">En ruta</button>
            <button type="button" class="btn admin-quick-links__item" data-filtro-estatus="entregado">Entregados</button>
            <button type="button" class="btn admin-quick-links__item" data-filtro-estatus="cancelado">Cancelados</button>
        </div>

        <p id="pedidos-status" class="brand-tagline"></p>

        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Modalidad</th>
                        <th>CP</th>
                        <th>Estatus</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody id="pedidos-tbody">
                    <tr><td colspan="7">Cargando pedidos...</td></tr>
                </tbody>
            </table>
        </div>
<?php
$pageScripts = ['../assets/js/admin-pedidos.js'];
require __DIR__ . '/layout/footer.php';
?>
