<?php

declare(strict_types=1);

// =============================================================================
// api/auth_middleware.php — Validación de Bearer JWT + RBAC
// Mandamiento #14: Todo endpoint POST/PUT/DELETE requiere autenticación real.
//
// INCLUIR después de cors.php y ANTES de la lógica de negocio.
// Si el token es inválido o ausente → HTTP 401 y termina el script.
//
// Expone: $authPayload (array con sub, email, role, iat, exp)
//
// Uso:
//   require_once __DIR__ . '/cors.php';
//   require_once __DIR__ . '/jwt.php';
//   require_once __DIR__ . '/auth_middleware.php';
//   // A partir de aquí $authPayload está disponible.
//   requireRole(ROLE_LEVEL_ADMIN, $authPayload);
//
// Blindaje Módulo 01 (Hito 22, database/003_modulo01_login_seguridad.sql):
//   1. Revocación inmediata — cada request autenticada vuelve a consultar la
//      BD (estatus + sesion_invalidada_en) en vez de confiar ciegamente en la
//      firma/expiración del JWT. Antes de este Hito, un usuario suspendido
//      seguía operando con su access token hasta que expirara solo (hasta 15
//      min); ahora se corta de inmediato. Costo aceptado explícitamente por
//      el Arquitecto: 1 consulta a BD por request autenticada, y que estos
//      endpoints ya no responden si la BD está caída (antes, con JWT puro,
//      sí podían).
//   2. Jerarquía numérica de roles (Módulo 01 §6) — reemplaza la lista plana
//      de roles permitidos por comparación de nivel: nunca un rol autoriza
//      algo por encima de su propio nivel, y agregar un rol intermedio no
//      exige tocar cada endpoint que ya usaba el nivel correcto.
// =============================================================================

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/conexion.php';

/**
 * Jerarquía numérica de roles (Módulo 01 §6). Único lugar donde se define —
 * agregar un rol nuevo es agregar una entrada aquí, nunca tocar cada
 * endpoint (Mandamiento #10: un solo nombre/valor válido por concepto).
 */
const ROLE_LEVELS = [
    'admin'       => 100,
    'staff'       => 50,
    'colaborador' => 10,
];

const ROLE_LEVEL_ADMIN       = 100;
const ROLE_LEVEL_STAFF       = 50;
const ROLE_LEVEL_COLABORADOR = 10;

/**
 * Verifica que el rol del usuario autenticado tenga, como mínimo, el nivel
 * numérico requerido. Un rol desconocido/no mapeado vale nivel 0 (nunca
 * autoriza nada). Si no alcanza el nivel → HTTP 403 y termina el script.
 *
 * @param int $nivelMinimo  Nivel mínimo requerido — usar las constantes
 *                          ROLE_LEVEL_* de arriba, nunca un número mágico.
 * @param array<string,mixed> $payload  El $authPayload expuesto por este middleware.
 */
function requireRole(int $nivelMinimo, array $payload): void
{
    $rol          = (string) ($payload['role'] ?? '');
    $nivelUsuario = ROLE_LEVELS[$rol] ?? 0;

    if ($nivelUsuario < $nivelMinimo) {
        http_response_code(403);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Acceso denegado. No tienes permisos para esta operación.',
            'data'    => [],
        ]);
        exit();
    }
}

/**
 * Device Binding — exige que el device_id del token coincida con el
 * device_id presentado en el header X-Device-Id del request actual.
 * Llamar después de obtener $authPayload, en endpoints sensibles
 * (ej. cambio de contraseña, datos de pago, cierre de sesión global).
 */
function requireDeviceBinding(array $payload, string $presentedDeviceId): void
{
    if (!jwtVerifyDevice($payload, $presentedDeviceId)) {
        http_response_code(401);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Token no corresponde a este dispositivo. Inicia sesión nuevamente.',
            'data'    => [],
        ]);
        exit();
    }
}

/** @var array<string,mixed> $authPayload */
$authPayload = (static function (): array {
    // ── Leer JWT_SECRET desde .env ────────────────────────────────────────────
    $envPath = dirname(__DIR__) . '/.env';
    $secret  = '';

    if (is_readable($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            // Strip comillas
            $len = strlen($val);
            if ($len >= 2 && (($val[0] === '"' && $val[$len - 1] === '"') || ($val[0] === "'" && $val[$len - 1] === "'"))) {
                $val = substr($val, 1, $len - 2);
            }
            if ($key === 'JWT_SECRET') {
                $secret = $val;
                break;
            }
        }
    }

    if ($secret === '') {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Configuración de seguridad incompleta.', 'data' => []]);
        exit();
    }

    // ── Extraer token del header Authorization: Bearer <token> ───────────────
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader === '' && function_exists('apache_request_headers')) {
        $headers    = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Autenticación requerida. Token no encontrado.', 'data' => []]);
        exit();
    }

    $token   = substr($authHeader, 7);
    // Solo se aceptan access tokens aquí. Un refresh token NUNCA debe usarse
    // como sesión — solo es válido contra /api/auth_refresh.php.
    $payload = jwtDecodeTyped($token, $secret, JWT_TYPE_ACCESS);

    if ($payload === null) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token inválido, expirado o de tipo incorrecto. Inicia sesión nuevamente.', 'data' => []]);
        exit();
    }

    // ── Revocación inmediata (Módulo 01, Hito 22) ─────────────────────────────
    // La firma/expiración del JWT ya son válidas, pero eso no basta: se
    // vuelve a consultar el estado REAL del usuario en BD en cada request.
    // La comparación de sesion_invalidada_en se hace del lado de MySQL
    // (FROM_UNIXTIME) para no depender de que el reloj de este proceso PHP
    // (local, vía túnel SSH) y el del servidor de BD coincidan exactamente.
    $userId = (int) ($payload['sub'] ?? 0);

    try {
        $pdo = (new Database())->getConnection();
    } catch (\Throwable) {
        // Database::getConnection() ya emite su propio error 500 y hace
        // exit() — este catch solo existe para que el análisis estático no
        // marque la ruta como "nunca retorna", nunca se alcanza en runtime.
        exit();
    }

    $checkStmt = $pdo->prepare(
        'SELECT (`estatus` = \'activo\') AS `activo`, '
        . '(`sesion_invalidada_en` IS NOT NULL AND `sesion_invalidada_en` > FROM_UNIXTIME(:iat)) AS `sesion_revocada` '
        . 'FROM `users` WHERE `id` = :id LIMIT 1'
    );
    $checkStmt->execute([':iat' => (int) ($payload['iat'] ?? 0), ':id' => $userId]);
    $estado = $checkStmt->fetch(\PDO::FETCH_ASSOC);

    if ($estado === false || !(bool) $estado['activo']) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Sesión no válida. Inicia sesión nuevamente.', 'data' => []]);
        exit();
    }

    if ((bool) $estado['sesion_revocada']) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Tu sesión fue revocada. Inicia sesión nuevamente.', 'data' => []]);
        exit();
    }

    return $payload;
})();
