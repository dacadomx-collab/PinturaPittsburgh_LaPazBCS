<?php

declare(strict_types=1);

// =============================================================================
// api/setup_diagnostico.php — Diagnóstico + Migración + Seed de Admin SIN CLI
//
// ⚠️⚠️⚠️ ARCHIVO TEMPORAL DE ARRANQUE — ELIMINAR DESPUÉS DE USARLO ⚠️⚠️⚠️
// Mismo patrón que api/admin/test_real_dispatch.php (documentado en
// knowledge/03) y api/diagnostico_bd.php (FUENTEDEVERDAD_CONSOLIDADA.md):
// ningún script de diagnóstico/arranque sobrevive al primer deploy real a
// producción (Mandamiento #18).
//
// Protegido por SETUP_TOKEN (.env) — nunca por contraseña de usuario. Se
// niega por completo si APP_ENV=production, sin importar el token.
//
// Uso (todo requiere ?token=<SETUP_TOKEN>):
//   GET  api/setup_diagnostico.php?token=...                    → solo diagnóstico
//   POST api/setup_diagnostico.php?token=...&action=migrate      → aplica 001 y 002
//   POST api/setup_diagnostico.php?token=...&action=seed_admin   → crea/actualiza el admin
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

const SETUP_ADMIN_EMAIL = 'dacadomx@yahoo.com';

$envPath = dirname(__DIR__) . '/.env';
if (!is_readable($envPath)) {
    send_error('Error crítico de servidor: Configuración no encontrada.', 500);
}
$env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];

// Bloqueo duro en producción — independiente del token.
if (($env['APP_ENV'] ?? '') === 'production') {
    send_error('Deshabilitado en producción.', 403);
}

$setupToken     = (string) ($env['SETUP_TOKEN'] ?? '');
$providedToken  = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

if ($setupToken === '' || $providedToken === '' || !hash_equals($setupToken, $providedToken)) {
    send_error('No autorizado.', 403);
}

$accion = (string) ($_GET['action'] ?? $_POST['action'] ?? 'status');

asfl_log('REQUEST', ['endpoint' => 'setup_diagnostico.php', 'accion' => $accion]);

$dbHost = (string) ($env['DB_HOST'] ?? '');

// ── PASO 1: chequeo de red crudo (TCP a :3306) ANTES de intentar PDO ─────────
// Distingue "el host no responde en la red" de "las credenciales fallan" —
// un timeout de PDO tarda mucho más y no aclara la causa (ver Hito 8).
$socketOk    = false;
$socketError = null;

$fp = @fsockopen($dbHost, 3306, $errno, $errstr, 5);
if ($fp !== false) {
    $socketOk = true;
    fclose($fp);
} else {
    $socketError = "{$errno}: {$errstr}";
}

$resultado = [
    'db_host'                 => $dbHost,
    'puerto_3306_alcanzable'  => $socketOk,
    'error_red'                => $socketError,
    'conexion_pdo'             => false,
    'tablas_existentes'        => [],
    'admin_ya_existe'          => null,
];

if (!$socketOk) {
    asfl_log('RESPONSE', ['endpoint' => 'setup_diagnostico.php', 'resultado' => 'sin_red']);
    send_success('Diagnóstico completado — sin conectividad de red al puerto 3306.', $resultado);
}

try {
    $pdo = (new Database())->getConnection();
    $resultado['conexion_pdo'] = true;

    $tablas = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    $resultado['tablas_existentes'] = $tablas;

    if (in_array('users', $tablas, true)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => SETUP_ADMIN_EMAIL]);
        $resultado['admin_ya_existe'] = $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    // ── action=migrate (solo POST — nunca por un GET accidental/crawler) ────
    if ($accion === 'migrate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tablasBase = ['users', 'productos', 'producto_presentaciones', 'codigos_postales_cobertura', 'pedidos', 'pedido_items', 'social_tokens', 'publicaciones_sociales'];

        if (array_diff($tablasBase, $tablas) !== []) {
            $sql = file_get_contents(dirname(__DIR__) . '/database/001_schema_inicial.sql');
            $pdo->exec((string) $sql);
            $resultado['migracion_001'] = 'ejecutada';
        } else {
            $resultado['migracion_001'] = 'ya_existia';
        }

        if (!in_array('banners', $tablas, true)) {
            $sql = file_get_contents(dirname(__DIR__) . '/database/002_banners_cupones_colaborador.sql');
            $pdo->exec((string) $sql);
            $resultado['migracion_002'] = 'ejecutada';
        } else {
            $resultado['migracion_002'] = 'ya_existia';
        }

        $resultado['tablas_existentes'] = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    }

    // ── action=seed_admin (solo POST) — usuario fijo, nunca arbitrario ──────
    if ($accion === 'seed_admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%&*';
        $password = '';
        for ($i = 0; $i < 16; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => SETUP_ADMIN_EMAIL]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($fila !== false) {
            $upd = $pdo->prepare("UPDATE users SET password_hash = :hash, role = 'admin', estatus = 'activo' WHERE id = :id");
            $upd->execute([':hash' => $hash, ':id' => $fila['id']]);
            $resultado['seed_admin'] = 'actualizado';
        } else {
            $ins = $pdo->prepare("INSERT INTO users (email, password_hash, role, estatus) VALUES (:email, :hash, 'admin', 'activo')");
            $ins->execute([':email' => SETUP_ADMIN_EMAIL, ':hash' => $hash]);
            $resultado['seed_admin'] = 'creado';
        }

        // Se muestra UNA sola vez en esta respuesta — nunca se guarda en texto plano.
        $resultado['password_temporal'] = $password;
        $resultado['admin_ya_existe'] = true;
    }
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [setup_diagnostico] ' . $e->getMessage());
    $resultado['error_pdo'] = 'Error de conexión o consulta — ver error_log del servidor.';
}

asfl_log('RESPONSE', ['endpoint' => 'setup_diagnostico.php', 'accion' => $accion, 'conexion_pdo' => $resultado['conexion_pdo']]);

send_success('Diagnóstico completado.', $resultado);
