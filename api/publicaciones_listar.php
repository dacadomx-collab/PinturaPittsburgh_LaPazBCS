<?php

declare(strict_types=1);

// =============================================================================
// api/publicaciones_listar.php — Feed Público de Publicaciones (Contrato 10)
// Endpoint: GET /api/publicaciones_listar.php?limit=6
// Auth: Público
//
// Diferencia deliberada con api/admin/social_historial.php: este endpoint
// SOLO expone estado='publicada' y jamás los campos internos de operación
// (social_token_id, media_container_id, post_id_externo, estado).
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

const PUBLICACIONES_LIMITE_DEFAULT = 6;
const PUBLICACIONES_LIMITE_MAXIMO  = 24;

asfl_log('REQUEST', ['endpoint' => 'publicaciones_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

$limit = sanitize_int($_GET['limit'] ?? PUBLICACIONES_LIMITE_DEFAULT, PUBLICACIONES_LIMITE_DEFAULT);
if ($limit <= 0) {
    $limit = PUBLICACIONES_LIMITE_DEFAULT;
}
$limit = min($limit, PUBLICACIONES_LIMITE_MAXIMO);

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare(
        "SELECT ps.id, st.plataforma, ps.producto_id, ps.texto, ps.media_url, ps.publicado_en
         FROM publicaciones_sociales ps
         INNER JOIN social_tokens st ON st.id = ps.social_token_id
         WHERE ps.estado = 'publicada'
         ORDER BY ps.publicado_en DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    $publicaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    asfl_log('RESPONSE', ['endpoint' => 'publicaciones_listar.php', 'total' => count($publicaciones)]);

    send_success('Publicaciones recientes.', ['publicaciones' => $publicaciones]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [publicaciones_listar] ' . $e->getMessage());
    send_error('Error interno al consultar publicaciones.', 500);
}
