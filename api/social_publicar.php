<?php

declare(strict_types=1);

// =============================================================================
// api/social_publicar.php — Publicador Social Omnicanal (Meta Graph API)
// Endpoint: POST /api/social_publicar.php
// Contrato: knowledge/03_CONTRATOS_API_Y_RUTAS.md — Contrato 6
// Auth: Bearer JWT + role=admin (Mandamiento #14)
//
// Facebook: publicación síncrona directa (POST /{page-id}/photos).
// Instagram: solo se ejecuta aquí la FASE 1 (crear contenedor de medios) —
// el sondeo de status_code y la publicación final (FASE 2/3) requieren un
// proceso en segundo plano (cron/worker) que aún no existe en este proyecto.
// La publicación queda en estado 'programada' con `media_container_id` hasta
// que ese worker la complete. No se implementa un sleep-loop síncrono aquí:
// bloquear un worker de Apache/PHP-FPM por segundos degradaría el servidor.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/auth_middleware.php'; // expone $authPayload
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/crypto_helper.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

requireRole(ROLE_LEVEL_ADMIN, $authPayload);

asfl_log('REQUEST', ['endpoint' => 'social_publicar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (!str_contains($contentType, 'application/json')) {
    send_error('Content-Type debe ser application/json.', 415);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$plataforma      = sanitize_string((string) ($payload['plataforma'] ?? ''), 20);
$texto           = sanitize_string((string) ($payload['texto'] ?? ''), 2200);
$mediaUrl        = sanitize_string((string) ($payload['media_url'] ?? ''), 500);
$productoId      = isset($payload['producto_id']) ? sanitize_int($payload['producto_id'], 0) : null;
$programadoPara  = isset($payload['programado_para']) ? sanitize_string((string) $payload['programado_para'], 30) : null;

if (!in_array($plataforma, ['facebook', 'instagram'], true)) {
    send_error('El campo "plataforma" debe ser "facebook" o "instagram".', 422);
}
if ($texto === '') {
    send_error('El campo "texto" es requerido.', 422);
}
if ($mediaUrl === '' || filter_var($mediaUrl, FILTER_VALIDATE_URL) === false || !str_starts_with($mediaUrl, 'https://')) {
    send_error('El campo "media_url" debe ser una URL HTTPS válida.', 422);
}

try {
    $pdo = (new Database())->getConnection();

    // ── Buscar el token vigente de la plataforma solicitada ──────────────────
    $stmt = $pdo->prepare(
        'SELECT id, plataforma, cuenta_id_externa, nonce, dek_envuelta, auth_tag, token_cifrado, expira_en
         FROM social_tokens WHERE plataforma = :plataforma
         ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([':plataforma' => $plataforma]);
    $tokenRow = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($tokenRow === false) {
        send_error("No hay una cuenta de {$plataforma} vinculada. Vincula una cuenta antes de publicar.", 422);
    }

    // ── Crear el registro de la publicación como borrador ────────────────────
    $estadoInicial = $programadoPara !== null ? 'programada' : 'borrador';

    $insert = $pdo->prepare(
        'INSERT INTO publicaciones_sociales
            (social_token_id, producto_id, texto, media_url, estado, programado_para)
         VALUES (:social_token_id, :producto_id, :texto, :media_url, :estado, :programado_para)'
    );
    $insert->execute([
        ':social_token_id' => $tokenRow['id'],
        ':producto_id'     => $productoId ?: null,
        ':texto'           => $texto,
        ':media_url'       => $mediaUrl,
        ':estado'          => $estadoInicial,
        ':programado_para' => $programadoPara,
    ]);
    $publicacionId = (int) $pdo->lastInsertId();

    // Publicación programada para el futuro: no se ejecuta ahora.
    if ($programadoPara !== null) {
        send_success('Publicación programada.', ['id' => $publicacionId, 'estado' => $estadoInicial]);
    }

    // ── Descifrar el token (Envelope Encryption) SOLO para esta llamada ──────
    // NOTA: este proyecto lee `.env` con parse_ini_file() (ver api/auth_login.php),
    // NO con getenv() — parse_ini_file() nunca hace putenv(), así que getenv()
    // siempre devolvería vacío aquí. Se sigue el mismo patrón que el resto de /api/.
    $env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $kek = (string) ($env['META_TOKEN_KEK'] ?? '');
    if ($kek === '') {
        send_error('Configuración de Meta Graph API incompleta (META_TOKEN_KEK).', 500);
    }

    $aad = [
        'id'                => (int) $tokenRow['id'],
        'plataforma'        => $tokenRow['plataforma'],
        'cuenta_id_externa' => $tokenRow['cuenta_id_externa'],
    ];

    try {
        $accessToken = envelopeDecrypt($tokenRow, $kek, $aad);
    } catch (\RuntimeException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [social_publicar] Fallo al descifrar token: ' . $e->getMessage());
        marcarFallida($pdo, $publicacionId);
        send_error('No fue posible autenticar con la cuenta social. Revisa el token vinculado.', 500);
    }

    $graphVersion = (string) ($env['META_GRAPH_API_VERSION'] ?? 'v25.0');
    $cuentaId     = $tokenRow['cuenta_id_externa'];

    if ($plataforma === 'facebook') {
        $resultado = publicarEnFacebook($graphVersion, $cuentaId, $accessToken, $mediaUrl, $texto);
    } else {
        $resultado = crearContenedorInstagram($graphVersion, $cuentaId, $accessToken, $mediaUrl, $texto);
    }

    zeroize($accessToken);

    if ($resultado['ok'] === false) {
        marcarFallida($pdo, $publicacionId);
        error_log('[' . date('Y-m-d H:i:s') . '] [social_publicar] Meta API error: ' . $resultado['error']);
        send_error('No fue posible completar la publicación en ' . $plataforma . '. Intenta más tarde.', 502);
    }

    if ($plataforma === 'facebook') {
        $update = $pdo->prepare(
            "UPDATE publicaciones_sociales SET estado = 'publicada', post_id_externo = :post_id, publicado_en = NOW() WHERE id = :id"
        );
        $update->execute([':post_id' => $resultado['post_id'], ':id' => $publicacionId]);

        send_success('Publicado en Facebook.', ['id' => $publicacionId, 'estado' => 'publicada', 'post_id_externo' => $resultado['post_id']]);
    }

    // Instagram: solo fase 1 completada (contenedor creado) — falta el worker
    // de sondeo + media_publish para llegar a 'publicada'.
    $update = $pdo->prepare(
        "UPDATE publicaciones_sociales SET estado = 'programada', media_container_id = :container_id WHERE id = :id"
    );
    $update->execute([':container_id' => $resultado['container_id'], ':id' => $publicacionId]);

    send_success(
        'Contenedor de Instagram creado. Publicación pendiente de confirmación asíncrona (FINISHED).',
        ['id' => $publicacionId, 'estado' => 'programada', 'media_container_id' => $resultado['container_id']]
    );
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [social_publicar] ' . $e->getMessage());
    send_error('Error interno al procesar la publicación.', 500);
}

// ── Helpers locales ───────────────────────────────────────────────────────────

function marcarFallida(\PDO $pdo, int $publicacionId): void
{
    $stmt = $pdo->prepare("UPDATE publicaciones_sociales SET estado = 'fallida' WHERE id = :id");
    $stmt->execute([':id' => $publicacionId]);
}

/** @return array{ok:bool,post_id?:string,error?:string} */
function publicarEnFacebook(string $graphVersion, string $pageId, string $accessToken, string $imageUrl, string $caption): array
{
    $url = "https://graph.facebook.com/{$graphVersion}/{$pageId}/photos";

    return llamarMetaGraphApi($url, [
        'url'          => $imageUrl,
        'caption'      => $caption,
        'access_token' => $accessToken,
    ], 'post_id', 'id');
}

/** @return array{ok:bool,container_id?:string,error?:string} */
function crearContenedorInstagram(string $graphVersion, string $igUserId, string $accessToken, string $imageUrl, string $caption): array
{
    $url = "https://graph.facebook.com/{$graphVersion}/{$igUserId}/media";

    return llamarMetaGraphApi($url, [
        'image_url'    => $imageUrl,
        'caption'      => $caption,
        'access_token' => $accessToken,
    ], 'container_id', 'id');
}

/**
 * POST genérico a Meta Graph API con Circuit Breaker — nunca deja escapar un
 * error fatal; siempre retorna un array tipado.
 *
 * @param array<string,string> $fields
 * @return array{ok:bool,error?:string}&array<string,string>
 */
function llamarMetaGraphApi(string $url, array $fields, string $resultKey, string $metaResponseField): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $responseBody = curl_exec($ch);
    $curlError    = curl_error($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseBody === false) {
        return ['ok' => false, 'error' => 'cURL: ' . $curlError];
    }

    $decoded = json_decode($responseBody, true);

    if ($httpCode !== 200 || !is_array($decoded) || !isset($decoded[$metaResponseField])) {
        return ['ok' => false, 'error' => 'Meta API HTTP ' . $httpCode . ': ' . $responseBody];
    }

    return ['ok' => true, $resultKey => (string) $decoded[$metaResponseField]];
}
