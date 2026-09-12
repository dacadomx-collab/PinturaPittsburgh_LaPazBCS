<?php

declare(strict_types=1);

// =============================================================================
// test_conexion.php — Diagnóstico de Salud de Base de Datos (Hito 14)
//
// ⚠️ ARCHIVO DE DIAGNÓSTICO — mismo criterio de blindaje que
// api/setup_diagnostico.php (protegido por SETUP_TOKEN, nunca por sesión de
// usuario; se autoniega por completo si APP_ENV=production). No fue pedido
// explícitamente en la directiva, pero este archivo consulta y muestra en
// pantalla los emails/roles reales de `users` — dejarlo abierto sin token
// sería una fuga de información evitable (Mandamiento #2, Seguridad Nivel
// Militar). Eliminar este archivo (o al menos SETUP_TOKEN de .env) en cuanto
// se termine de diagnosticar la conexión remota.
//
// Uso: test_conexion.php?token=<SETUP_TOKEN>
//
// NOTA DE ARQUITECTURA (staging en cPanel — Hito 14): si este script corre
// DENTRO del servidor de staging (pittsburgh.tourfindy.com, cPanel
// `tourfindycom`), el `.env` de ESE servidor debe usar `DB_HOST=localhost`,
// no el hostname público (`chir205.websitehostserver.net`). Dentro de la
// misma cuenta de hosting, MySQL habla por socket Unix local — nunca
// atraviesa el firewall perimetral del puerto 3306 que sí aplica a
// conexiones externas (como las de este entorno de desarrollo local, que
// SIEMPRE debe seguir usando el hostname público — Regla Cero, ver
// api/conexion.php). Confundir ambos valores es la causa más probable de
// que la migración/seed de Hito 12/13 nunca se haya podido ejecutar.
//
// Por qué NO se usa (new Database())->getConnection() para el intento de
// conexión: esa función atrapa su propia PDOException, registra un mensaje
// genérico y hace exit() con un JSON de error — diseño correcto para un
// endpoint de API (Mandamiento #2: nunca mostrar el error real de PDO al
// frontend), pero incompatible con el objetivo de ESTE archivo, que es
// precisamente mostrar una categoría de error clara para diagnóstico
// humano. Este archivo hace su propio intento de conexión para poder
// clasificar el error (no para evitar la clase centralizada) y, si la
// conexión funciona, ejecuta la consulta real a través de esa misma clase
// (`api/conexion.php`), demostrando que la ruta oficial también funciona.
// =============================================================================

require_once __DIR__ . '/api/conexion.php';

$envPath = __DIR__ . '/.env';
if (!is_readable($envPath)) {
    http_response_code(500);
    exit('Error crítico: no se encontró .env en la raíz del proyecto.');
}
$env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];

if (($env['APP_ENV'] ?? '') === 'production') {
    http_response_code(403);
    exit('Deshabilitado en producción.');
}

$setupToken    = (string) ($env['SETUP_TOKEN'] ?? '');
$providedToken = (string) ($_GET['token'] ?? '');

if ($setupToken === '' || $providedToken === '' || !hash_equals($setupToken, $providedToken)) {
    http_response_code(403);
    exit('No autorizado. Uso: test_conexion.php?token=&lt;SETUP_TOKEN&gt;');
}

$dbHost = (string) ($env['DB_HOST'] ?? '');
$dbName = (string) ($env['DB_NAME'] ?? '');
$dbUser = (string) ($env['DB_USER'] ?? '');
$dbPass = (string) ($env['DB_PASS'] ?? '');

$categoria  = null;
$detalle    = '';
$usuarios   = null;
$conexionOk = false;

// ── Paso 1: chequeo de red crudo (TCP a :3306) — igual que setup_diagnostico.php ─
$socketOk = false;
$fp = @fsockopen($dbHost, 3306, $errno, $errstr, 5);
if ($fp !== false) {
    $socketOk = true;
    fclose($fp);
} else {
    $categoria = 'red';
    $detalle   = "{$errno}: {$errstr}";
}

// ── Paso 2: intento real de PDO (solo si el socket respondió) ────────────────
if ($socketOk) {
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdoDiagnostico = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE         => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT         => 5,
        ]);
        $conexionOk = true;
    } catch (\PDOException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [test_conexion] ' . $e->getMessage());

        if (preg_match('/\[(\d+)\]/', $e->getMessage(), $m)) {
            $codigoMysql = $m[1];
        } else {
            $codigoMysql = null;
        }

        $categoria = match ($codigoMysql) {
            '2002', '2003' => 'red',
            '1045', '1044' => 'credenciales',
            '1049'          => 'bd_inexistente',
            default         => 'desconocido',
        };
        $detalle = $codigoMysql !== null ? "Código MySQL {$codigoMysql}" : 'Sin código MySQL identificable';
    }
}

// ── Paso 3: si la conexión funcionó, consulta real vía la clase centralizada ──
if ($conexionOk) {
    try {
        $pdo = (new Database())->getConnection();
        $stmt = $pdo->query('SELECT id, email, role, estatus FROM users ORDER BY id ASC');
        $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [test_conexion] consulta users: ' . $e->getMessage());
        $categoria = 'consulta';
        $detalle   = 'La conexión abrió correctamente pero la consulta a `users` falló — ver error_log.';
    }
}

$guia = [
    'red'             => 'Timeout de socket / firewall (equivalente a WSAETIMEDOUT / errno 10060). El puerto 3306 no respondió en 5 segundos. Si este script corre DENTRO del servidor de staging, cambia DB_HOST a "localhost" en el .env de ESE servidor (ver nota de arquitectura arriba). Si corre desde fuera (como este entorno de desarrollo), verifica en cPanel → Remote MySQL que el wildcard % esté realmente guardado, y pide a soporte del hosting que confirme que no hay un firewall perimetral (CSF) bloqueando el puerto.',
    'credenciales'    => 'Usuario o contraseña de MySQL incorrectos (Access Denied). Verifica DB_USER/DB_PASS en el .env de este servidor contra lo dado de alta en cPanel → MySQL Databases.',
    'bd_inexistente'  => 'La base de datos indicada en DB_NAME no existe en este servidor MySQL. Verifica el nombre exacto en cPanel → MySQL Databases (suele llevar un prefijo de cuenta, ej. "usuario_nombrebd").',
    'consulta'        => 'La conexión se estableció, pero la tabla `users` no existe o la consulta falló — ejecuta primero la migración (api/setup_diagnostico.php?action=migrate).',
    'desconocido'     => 'Error de conexión no clasificado en las categorías anteriores — revisa el error_log del servidor para el mensaje completo de PDO.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Conexión — PinturaPittsburgh</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<main class="container">
    <h1>Diagnóstico de Conexión a Base de Datos</h1>
    <p class="brand-tagline">DB_HOST configurado: <code><?php echo htmlspecialchars($dbHost, ENT_QUOTES, 'UTF-8'); ?></code></p>

    <?php if ($conexionOk && is_array($usuarios)): ?>
        <div class="card">
            <h2>✅ Conexión exitosa</h2>
            <p>PDO conectó correctamente y la consulta a <code>users</code> devolvió <?php echo count($usuarios); ?> fila(s).</p>
        </div>
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr><th>ID</th><th>Email</th><th>Rol</th><th>Estatus</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $u['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $u['estatus'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>❌ Conexión fallida</h2>
            <p><strong>Categoría:</strong> <?php echo htmlspecialchars($categoria ?? 'desconocido', ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Detalle:</strong> <?php echo htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Guía técnica:</strong> <?php echo htmlspecialchars($guia[$categoria ?? 'desconocido'] ?? $guia['desconocido'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
