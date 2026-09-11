<?php

declare(strict_types=1);

// =============================================================================
// api/admin/social_historial.php — Historial de Publicaciones Sociales
// GET /api/admin/social_historial.php
// Auth: Bearer JWT + role=admin
//
// Extensión mínima no contemplada explícitamente en el Contrato 6 original,
// pero necesaria para que admin/social.html muestre el feed de publicaciones
// recientes (Hito 3, Tarea 3) tras un refresh de página — sin esta lectura
// persistida, "historial" solo podría vivir en memoria del navegador y se
// perdería al recargar. Registrado en knowledge/03_CONTRATOS_API_Y_RUTAS.md.
// =============================================================================

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../auth_middleware.php'; // expone $authPayload
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../../helpers/response.php';

requireRole(['admin'], $authPayload);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->query(
        'SELECT ps.id, st.plataforma, ps.producto_id, ps.texto, ps.media_url, ps.estado,
                ps.programado_para, ps.media_container_id, ps.post_id_externo, ps.publicado_en,
                ps.created_at
         FROM publicaciones_sociales ps
         INNER JOIN social_tokens st ON st.id = ps.social_token_id
         ORDER BY ps.created_at DESC
         LIMIT 50'
    );
    $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    send_success('Historial de publicaciones.', ['publicaciones' => $filas]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [social_historial] ' . $e->getMessage());
    send_error('Error interno al consultar el historial.', 500);
}
