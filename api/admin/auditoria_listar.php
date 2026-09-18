<?php

declare(strict_types=1);

// =============================================================================
// api/admin/auditoria_listar.php — Visor de Bitácora de Accesos (Contrato 16,
// Hito 23, Módulo 01 §9.3/§9.6)
// GET → lista paginada de `log_actividad` (tabla append-only, Hito 22),
// con filtros opcionales por evento y rango de fechas.
// Auth: Bearer JWT + role=admin (nivel 100).
// Nunca expone IP/User-Agent en claro — ip_hash/device_hash ya llegan
// anonimizados (SHA-256) desde la propia tabla, este endpoint solo los lee.
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

$eventosValidos = ['login_exitoso', 'login_fallido', 'cuenta_bloqueada', 'sesion_invalidada'];
$eventoFiltro    = (string) ($_GET['evento'] ?? '');

$fechaDesde = (string) ($_GET['fecha_desde'] ?? '');
$fechaHasta = (string) ($_GET['fecha_hasta'] ?? '');
$formatoFechaValido = static fn (string $f): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) === 1;

$pagina = max(1, sanitize_int($_GET['pagina'] ?? 1, 1));
$limite = sanitize_int($_GET['limite'] ?? 20, 20);
$limite = max(1, min($limite, 100));
$offset = ($pagina - 1) * $limite;

try {
    $pdo = (new Database())->getConnection();

    $where  = [];
    $params = [];

    if (in_array($eventoFiltro, $eventosValidos, true)) {
        $where[]            = 'la.`evento` = :evento';
        $params[':evento']  = $eventoFiltro;
    }
    if ($formatoFechaValido($fechaDesde)) {
        $where[]            = 'la.`creado_en` >= :fecha_desde';
        $params[':fecha_desde'] = $fechaDesde . ' 00:00:00';
    }
    if ($formatoFechaValido($fechaHasta)) {
        $where[]            = 'la.`creado_en` < DATE_ADD(:fecha_hasta, INTERVAL 1 DAY)';
        $params[':fecha_hasta'] = $fechaHasta;
    }

    $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM `log_actividad` la' . $whereSql);
    $totalStmt->execute($params);
    $total = (int) $totalStmt->fetchColumn();

    $sql = 'SELECT la.`id`, la.`usuario_id`, u.`email` AS `usuario_email`, la.`evento`, '
        . 'la.`ip_hash`, la.`device_hash`, la.`detalle`, la.`creado_en` '
        . 'FROM `log_actividad` la LEFT JOIN `users` u ON u.`id` = la.`usuario_id`'
        . $whereSql
        . ' ORDER BY la.`id` DESC LIMIT ' . $limite . ' OFFSET ' . $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    send_success('Bitácora listada.', [
        'registros'      => $registros,
        'total'          => $total,
        'pagina'         => $pagina,
        'total_paginas'  => (int) max(1, ceil($total / $limite)),
    ]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [auditoria_listar] ' . $e->getMessage());
    send_error('Error interno al listar la bitácora.', 500);
}
