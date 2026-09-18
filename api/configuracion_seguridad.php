<?php

declare(strict_types=1);

// =============================================================================
// api/configuracion_seguridad.php — Motor de Política de Contraseñas (Módulo 01 §7)
// GET  → público, sin auth: expone la DEFINICIÓN de la política activa
//        (longitud_minima, requiere_mayuscula, etc.) para que el medidor de
//        fuerza del frontend calibre umbrales sin duplicar reglas en JS.
//        Nunca expone max_intentos_fallidos/minutos_bloqueo (Módulo 01 §7.4:
//        "nunca información... que pudiera ayudar a un atacante a enumerar
//        el estado del sistema" — los umbrales de rate limiting se quedan
//        server-side, solo los consume api/auth_login.php).
// PUT/POST → protegido, nivel admin (100): actualiza politica_password +
//        max_intentos_fallidos + minutos_bloqueo (Módulo 01 §7.3, adaptado:
//        el blueprint exige 'super_admin', este proyecto solo tiene 'admin'
//        como techo de la jerarquía — ver ROLE_LEVEL_ADMIN en auth_middleware.php).
// Mandamiento #2: Seguridad Nivel Militar | Mandamiento #14: CORS ≠ Auth
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/password_policy.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'PUT', 'POST'], true)) {
    send_error('Método no permitido.', 405);
}

try {
    $pdo = (new Database())->getConnection();

    // ── GET: lectura pública ────────────────────────────────────────────────
    if ($method === 'GET') {
        $perfil     = obtenerPoliticaActiva($pdo);
        $definicion = politicaSeguridadDefinicion($perfil);

        send_success('Política de contraseña activa.', array_merge(
            ['politica_password' => $perfil],
            $definicion
        ));
    }

    // ── PUT/POST: mutación, exclusiva de admin (nivel 100) ──────────────────
    require_once __DIR__ . '/jwt.php';
    require_once __DIR__ . '/auth_middleware.php'; // expone $authPayload

    requireRole(ROLE_LEVEL_ADMIN, $authPayload);

    try {
        $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        send_error('Payload JSON inválido.', 400);
    }

    $politicaPassword    = sanitize_string((string) ($payload['politica_password'] ?? ''), 10);
    $maxIntentosFallidos = sanitize_int($payload['max_intentos_fallidos'] ?? null, -1);
    $minutosBloqueo      = sanitize_int($payload['minutos_bloqueo'] ?? null, -1);

    if (!in_array($politicaPassword, ['simple', 'media', 'fuerte'], true)) {
        send_error('politica_password debe ser simple, media o fuerte.', 422);
    }
    // Cotas operativas razonables (no forman parte del blueprint del Módulo
    // 01, que no fija límites numéricos para estos 2 parámetros) — evitan
    // que el panel de admin deje el sistema en un estado absurdo (ej. 0
    // intentos, o un bloqueo de varios días).
    if ($maxIntentosFallidos < 1 || $maxIntentosFallidos > 20) {
        send_error('max_intentos_fallidos debe estar entre 1 y 20.', 422);
    }
    if ($minutosBloqueo < 1 || $minutosBloqueo > 1440) {
        send_error('minutos_bloqueo debe estar entre 1 y 1440 (24 horas).', 422);
    }

    $update = $pdo->prepare(
        'UPDATE `configuracion_seguridad` SET `politica_password` = :politica, '
        . '`max_intentos_fallidos` = :max_intentos, `minutos_bloqueo` = :minutos WHERE `id` = 1'
    );
    $update->execute([
        ':politica'     => $politicaPassword,
        ':max_intentos' => $maxIntentosFallidos,
        ':minutos'      => $minutosBloqueo,
    ]);

    send_success('Configuración de seguridad actualizada.', [
        'politica_password'     => $politicaPassword,
        'max_intentos_fallidos' => $maxIntentosFallidos,
        'minutos_bloqueo'       => $minutosBloqueo,
    ]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [configuracion_seguridad] ' . $e->getMessage());
    send_error('Error interno al procesar la configuración de seguridad.', 500);
}
