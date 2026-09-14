<?php

declare(strict_types=1);

// =============================================================================
// api/auth_login.php — Login JWT Enterprise (Access + Refresh + Device Binding)
// Endpoint: POST /api/auth_login.php
// Mandamiento #2: Seguridad Nivel Militar | Mandamiento #14: CORS ≠ Auth
//
// Blindaje Módulo 01 (Hito 22, database/003_modulo01_login_seguridad.sql):
//   1. Estatus — cuentas 'inactivo' se rechazan con el mismo mensaje genérico
//      que una contraseña incorrecta (Zero Enumeration).
//   2. Timing attack — cuando el correo no existe se ejecuta password_verify()
//      contra un hash BCrypt constante (DUMMY_HASH) para igualar el tiempo de
//      respuesta frente al caso "existe pero contraseña incorrecta".
//   3. Rate limiting / tarpitting — `users.intentos_fallidos`/`bloqueado_hasta`,
//      umbrales leídos de `configuracion_seguridad` (fila única id=1).
//   4. Bitácora inmutable — cada intento (éxito o fallo) se inserta en
//      `log_actividad` vía helpers/security_log.php (nunca IP/UA en claro).
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';
require_once __DIR__ . '/../helpers/security_log.php';
require_once __DIR__ . '/../validators/validator.php';

// Hash BCrypt constante, de un valor que nunca es una contraseña real — se
// verifica contra él cuando el correo no existe, para que la respuesta tome
// aproximadamente el mismo tiempo que un password_verify() real y así no
// delatar por temporización si un correo está o no registrado.
const DUMMY_HASH = '$2y$12$.0SjLT8SWlJYRBevU/HR7ef4J5/XfXc3AKjwCeBBnsc3zzXIyInUq';

asfl_log('REQUEST', ['endpoint' => 'auth_login.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$email    = sanitize_email((string) ($payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if (!is_valid_email($email)) {
    send_error('Correo electrónico inválido.', 422);
}
if ($password === '') {
    send_error('La contraseña es requerida.', 422);
}

// device_id: preferir el enviado por el cliente (UUID persistido en el
// dispositivo); si no llega, derivar uno desde IP + User-Agent.
$deviceId = sanitize_string((string) ($payload['device_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? '')));
if ($deviceId === '') {
    $deviceId = jwtMakeDeviceId();
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $stmt = $pdo->prepare(
        'SELECT `id`, `email`, `password_hash`, `role`, `estatus`, `intentos_fallidos`, '
        . '(`bloqueado_hasta` IS NOT NULL AND `bloqueado_hasta` > NOW()) AS `esta_bloqueado` '
        . 'FROM `users` WHERE `email` = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    // ── Correo no registrado: dummy hash para igualar tiempos, cero pistas ──
    if ($user === false) {
        password_verify($password, DUMMY_HASH);
        registrarEventoSeguridad($pdo, null, 'login_fallido', 'correo_no_registrado');
        asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'error', 'reason' => 'credenciales_invalidas']);
        send_error('Credenciales inválidas.', 401);
    }

    $userId = (int) $user['id'];

    // ── Cuenta bloqueada por rate limiting: rechazo inmediato, sin bcrypt ──
    if ((bool) $user['esta_bloqueado']) {
        registrarEventoSeguridad($pdo, $userId, 'cuenta_bloqueada');
        asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'error', 'reason' => 'cuenta_bloqueada']);
        send_error('Demasiados intentos fallidos. Cuenta temporalmente bloqueada.', 429);
    }

    $passwordOk = password_verify($password, (string) $user['password_hash']);

    // Estatus se evalúa DESPUÉS del password_verify (nunca antes) para no
    // introducir una diferencia de tiempo medible entre "suspendido" y
    // "contraseña incorrecta" — ambos casos comparten el mismo mensaje 401.
    if (!$passwordOk || (string) $user['estatus'] !== 'activo') {
        if (!$passwordOk) {
            $cfgStmt = $pdo->query(
                'SELECT `max_intentos_fallidos`, `minutos_bloqueo` FROM `configuracion_seguridad` WHERE `id` = 1'
            );
            $cfg = $cfgStmt->fetch(\PDO::FETCH_ASSOC) ?: ['max_intentos_fallidos' => 5, 'minutos_bloqueo' => 15];

            $nuevosIntentos = (int) $user['intentos_fallidos'] + 1;

            if ($nuevosIntentos >= (int) $cfg['max_intentos_fallidos']) {
                $update = $pdo->prepare(
                    'UPDATE `users` SET `intentos_fallidos` = 0, '
                    . '`bloqueado_hasta` = DATE_ADD(NOW(), INTERVAL :minutos MINUTE) WHERE `id` = :id'
                );
                $update->execute([':minutos' => (int) $cfg['minutos_bloqueo'], ':id' => $userId]);
                registrarEventoSeguridad($pdo, $userId, 'cuenta_bloqueada', 'umbral_alcanzado');
            } else {
                $update = $pdo->prepare('UPDATE `users` SET `intentos_fallidos` = :n WHERE `id` = :id');
                $update->execute([':n' => $nuevosIntentos, ':id' => $userId]);
            }
        }

        registrarEventoSeguridad($pdo, $userId, 'login_fallido', $passwordOk ? 'cuenta_inactiva' : 'password_incorrecto');
        asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'error', 'reason' => 'credenciales_invalidas']);
        send_error('Credenciales inválidas.', 401);
    }

    // ── Login exitoso: reset del contador de intentos y emisión de tokens ──
    $reset = $pdo->prepare('UPDATE `users` SET `intentos_fallidos` = 0, `bloqueado_hasta` = NULL WHERE `id` = :id');
    $reset->execute([':id' => $userId]);

    registrarEventoSeguridad($pdo, $userId, 'login_exitoso');

    $env        = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $secret     = (string) ($env['JWT_SECRET'] ?? '');
    $accessTtl  = (int) ($env['JWT_ACCESS_TTL'] ?? 900);
    $refreshTtl = (int) ($env['JWT_REFRESH_TTL'] ?? 2592000);

    if ($secret === '') {
        send_error('Configuración de seguridad incompleta.', 500);
    }

    $claims = [
        'sub'   => $userId,
        'email' => (string) $user['email'],
        'role'  => (string) $user['role'],
    ];

    $accessToken  = jwtEncodeAccess($claims, $secret, $deviceId, $accessTtl);
    $refreshToken = jwtEncodeRefresh($claims, $secret, $deviceId, $refreshTtl);

    asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'success', 'user_id' => $userId]);

    send_success('Autenticación exitosa.', [
        'access_token'  => $accessToken,
        'refresh_token' => $refreshToken,
        'device_id'     => $deviceId,
        'expires_in'    => $accessTtl,
        'role'          => $user['role'],
    ]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [auth_login] ' . $e->getMessage());
    send_error('Error interno al procesar el inicio de sesión.', 500);
}
