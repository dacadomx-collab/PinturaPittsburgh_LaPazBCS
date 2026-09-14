<?php

declare(strict_types=1);

// =============================================================================
// api/banners_listar.php — Banners de Cabecera (Contrato 11)
// Endpoint: GET /api/banners_listar.php
// Auth: Público
// Depende de: tabla `banners` (database/002_banners_cupones_colaborador.sql)
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'banners_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->query(
        "SELECT id, titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden, ubicacion
         FROM banners
         WHERE activo = 1
         ORDER BY orden ASC, id ASC"
    );
    $banners = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    asfl_log('RESPONSE', ['endpoint' => 'banners_listar.php', 'total' => count($banners)]);

    send_success('Banners activos.', ['banners' => $banners]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [banners_listar] ' . $e->getMessage());
    send_error('Error interno al consultar banners.', 500);
}
