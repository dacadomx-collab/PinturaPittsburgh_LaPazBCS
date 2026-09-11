<?php

declare(strict_types=1);

// =============================================================================
// workers/instagram_worker.php — Fases 2 y 3 del Pipeline de Instagram
// Ejecución: SOLO CLI (cron). Bloqueado por HTTP en .htaccess (defensa doble).
//
// Uso: php workers/instagram_worker.php
// Cron sugerido (cada 1-2 minutos): * * * * * php /ruta/workers/instagram_worker.php
//
// Continúa el trabajo de api/social_publicar.php (que solo ejecuta la fase 1:
// crear el contenedor de medios). Este worker:
//   1. Busca publicaciones 'programada' con media_container_id pendiente.
//   2. Sondea GET /{container_id}?fields=status_code en Meta Graph API.
//   3. Si FINISHED: publica con POST /{ig-user-id}/media_publish.
//   4. Si ERROR o excede el tiempo máximo de espera: marca 'fallida'.
//
// Mandamiento #13: no es telemetría de negocio — solo logging operativo vía
// helpers/asfl_logger.php (no-op fuera de APP_ENV=local) y error_log().
// =============================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta por CLI (cron), nunca por HTTP.');
}

require_once __DIR__ . '/../api/conexion.php';
require_once __DIR__ . '/../helpers/crypto_helper.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

const INSTAGRAM_MAX_ESPERA_MINUTOS = 15; // Tiempo máximo antes de marcar 'fallida' (sin columna de reintentos en schema — Mandamiento #9).

function log_worker(string $mensaje): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL;
}

/** @return array{ok:bool,status_code?:string,error?:string} */
function consultarStatusContenedor(string $graphVersion, string $containerId, string $accessToken): array
{
    $url = "https://graph.facebook.com/{$graphVersion}/{$containerId}?fields=status_code&access_token=" . urlencode($accessToken);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body     = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'cURL: ' . $curlErr];
    }

    $decoded = json_decode($body, true);
    if ($httpCode !== 200 || !isset($decoded['status_code'])) {
        return ['ok' => false, 'error' => 'Meta API HTTP ' . $httpCode . ': ' . $body];
    }

    return ['ok' => true, 'status_code' => (string) $decoded['status_code']];
}

/** @return array{ok:bool,post_id?:string,error?:string} */
function publicarContenedor(string $graphVersion, string $igUserId, string $containerId, string $accessToken): array
{
    $url = "https://graph.facebook.com/{$graphVersion}/{$igUserId}/media_publish";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'creation_id'  => $containerId,
            'access_token' => $accessToken,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body     = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'cURL: ' . $curlErr];
    }

    $decoded = json_decode($body, true);
    if ($httpCode !== 200 || !isset($decoded['id'])) {
        return ['ok' => false, 'error' => 'Meta API HTTP ' . $httpCode . ': ' . $body];
    }

    return ['ok' => true, 'post_id' => (string) $decoded['id']];
}

function marcarFallida(\PDO $pdo, int $publicacionId, string $razon): void
{
    $stmt = $pdo->prepare("UPDATE publicaciones_sociales SET estado = 'fallida' WHERE id = :id");
    $stmt->execute([':id' => $publicacionId]);
    error_log('[' . date('Y-m-d H:i:s') . '] [instagram_worker] Publicación #' . $publicacionId . ' marcada fallida: ' . $razon);
    asfl_log('SISTEMA', ['worker' => 'instagram_worker', 'publicacion_id' => $publicacionId, 'accion' => 'FALLIDA', 'razon' => $razon]);
}

// ── EJECUCIÓN PRINCIPAL ───────────────────────────────────────────────────────

$envPath = dirname(__DIR__) . '/.env';
if (!is_readable($envPath)) {
    log_worker('ERROR: no existe .env en la raíz del proyecto. Abortando.');
    exit(1);
}

$env          = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
$kek          = (string) ($env['META_TOKEN_KEK'] ?? '');
$graphVersion = (string) ($env['META_GRAPH_API_VERSION'] ?? 'v25.0');

if ($kek === '') {
    log_worker('ERROR: META_TOKEN_KEK no configurada en .env. Abortando.');
    exit(1);
}

try {
    $pdo = (new Database())->getConnection();
} catch (\Throwable $e) {
    log_worker('ERROR: no fue posible conectar a la base de datos: ' . $e->getMessage());
    exit(1);
}

$stmt = $pdo->query(
    "SELECT ps.id, ps.media_container_id, ps.created_at,
            st.id AS social_token_id, st.plataforma, st.cuenta_id_externa,
            st.nonce, st.dek_envuelta, st.auth_tag, st.token_cifrado
     FROM publicaciones_sociales ps
     INNER JOIN social_tokens st ON st.id = ps.social_token_id
     WHERE ps.estado = 'programada' AND ps.media_container_id IS NOT NULL"
);
$pendientes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

log_worker(count($pendientes) . ' publicación(es) de Instagram pendientes de confirmar.');

foreach ($pendientes as $fila) {
    $publicacionId = (int) $fila['id'];
    $containerId   = (string) $fila['media_container_id'];
    $igUserId      = (string) $fila['cuenta_id_externa'];

    $aad = [
        'id'                => (int) $fila['social_token_id'],
        'plataforma'        => $fila['plataforma'],
        'cuenta_id_externa' => $fila['cuenta_id_externa'],
    ];

    try {
        $accessToken = envelopeDecrypt($fila, $kek, $aad);
    } catch (\RuntimeException $e) {
        marcarFallida($pdo, $publicacionId, 'Fallo al descifrar token: ' . $e->getMessage());
        continue;
    }

    $status = consultarStatusContenedor($graphVersion, $containerId, $accessToken);

    if ($status['ok'] === false) {
        zeroize($accessToken);
        log_worker('Publicación #' . $publicacionId . ': error al consultar status_code — ' . $status['error']);
        // No se marca 'fallida' de inmediato por un error transitorio de red —
        // se reintenta en la siguiente corrida del cron, sujeto al límite de espera abajo.
    } elseif ($status['status_code'] === 'FINISHED') {
        $resultado = publicarContenedor($graphVersion, $igUserId, $containerId, $accessToken);
        zeroize($accessToken);

        if ($resultado['ok']) {
            $update = $pdo->prepare(
                "UPDATE publicaciones_sociales SET estado = 'publicada', post_id_externo = :post_id, publicado_en = NOW() WHERE id = :id"
            );
            $update->execute([':post_id' => $resultado['post_id'], ':id' => $publicacionId]);
            log_worker('Publicación #' . $publicacionId . ': publicada en Instagram (post_id=' . $resultado['post_id'] . ').');
            asfl_log('SISTEMA', ['worker' => 'instagram_worker', 'publicacion_id' => $publicacionId, 'accion' => 'PUBLICADA']);
        } else {
            marcarFallida($pdo, $publicacionId, 'media_publish falló: ' . $resultado['error']);
        }
        continue;
    } elseif ($status['status_code'] === 'ERROR') {
        zeroize($accessToken);
        marcarFallida($pdo, $publicacionId, 'Meta reportó status_code=ERROR en el contenedor.');
        continue;
    } else {
        // IN_PROGRESS u otro estado transitorio — se reintenta en la siguiente corrida.
        zeroize($accessToken);
        log_worker('Publicación #' . $publicacionId . ': aún en proceso (status_code=' . $status['status_code'] . ').');
    }

    // Límite de espera — evita reintentos indefinidos sobre un contenedor colgado.
    $creadoEn = strtotime((string) $fila['created_at']);
    $minutosTranscurridos = ($creadoEn !== false) ? (time() - $creadoEn) / 60 : 0;
    if ($minutosTranscurridos > INSTAGRAM_MAX_ESPERA_MINUTOS) {
        marcarFallida($pdo, $publicacionId, 'Excedió ' . INSTAGRAM_MAX_ESPERA_MINUTOS . ' minutos sin confirmación de Meta.');
    }
}

log_worker('Corrida del worker finalizada.');
