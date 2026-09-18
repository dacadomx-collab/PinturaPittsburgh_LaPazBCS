<?php

declare(strict_types=1);

// =============================================================================
// api/admin/pedidos_listar.php — Listado de Pedidos (Contrato 13, Hito 12/Directiva 2)
// GET → lista pedidos recientes con sus renglones (pedido_items), para el
//       panel admin/pedidos.php y las tarjetas KPI de admin/index.php.
// Auth: Bearer JWT + role=admin (mismo criterio que api/admin/catalogo_admin.php
// y api/admin/social_historial.php — Mandamiento #14).
// Filtros opcionales por querystring: ?estatus=pendiente&limite=20
// =============================================================================

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../auth_middleware.php'; // expone $authPayload
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/input_sanitizer.php';

requireRole(ROLE_LEVEL_ADMIN, $authPayload);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

$pdo = (new Database())->getConnection();

$estatusPermitidos = ['pendiente', 'confirmado', 'en_ruta', 'entregado', 'cancelado'];
$estatusFiltro      = (string) ($_GET['estatus'] ?? '');
$limite             = sanitize_int($_GET['limite'] ?? 20, 20);
$limite             = max(1, min($limite, 100));

try {
    $sql    = 'SELECT id, cliente_nombre, cliente_telefono, cliente_email, cp_entrega,
                       direccion_entrega, modalidad, estatus, total, created_at
                FROM pedidos';
    $params = [];

    if (in_array($estatusFiltro, $estatusPermitidos, true)) {
        $sql             .= ' WHERE estatus = :estatus';
        $params[':estatus'] = $estatusFiltro;
    }

    $sql .= ' ORDER BY created_at DESC LIMIT ' . $limite;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $totalPendientes = (int) $pdo->query(
        "SELECT COUNT(*) FROM pedidos WHERE estatus = 'pendiente'"
    )->fetchColumn();

    $totalGeneral = (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();

    send_success('Pedidos listados.', [
        'pedidos'           => $pedidos,
        'total_pendientes'  => $totalPendientes,
        'total_general'     => $totalGeneral,
    ]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [pedidos_listar] ' . $e->getMessage());
    send_error('Error interno al listar pedidos.', 500);
}
