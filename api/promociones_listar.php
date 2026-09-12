<?php

declare(strict_types=1);

// =============================================================================
// api/promociones_listar.php — Promociones y Cupones Vigentes (Contrato 12)
// Endpoint: GET /api/promociones_listar.php
// Auth: Público
// Depende de: tabla `cupones` (database/002_banners_cupones_colaborador.sql)
//
// Nunca expone si un código ya fue usado ni datos de quién lo canjeó — esta
// tabla no lleva ese detalle (es un catálogo de promociones, no un ledger de
// canjes). La validación de un código al aplicarlo en checkout es lógica
// futura, no de este endpoint de solo lectura.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'promociones_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->query(
        "SELECT id, codigo, badge, descripcion, imagen_url, url, tipo_descuento, valor_descuento, fecha_inicio, fecha_fin
         FROM cupones
         WHERE activo = 1
           AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
         ORDER BY fecha_fin ASC"
    );
    $promociones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    asfl_log('RESPONSE', ['endpoint' => 'promociones_listar.php', 'total' => count($promociones)]);

    send_success('Promociones vigentes.', ['promociones' => $promociones]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [promociones_listar] ' . $e->getMessage());
    send_error('Error interno al consultar promociones.', 500);
}
