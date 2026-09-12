<?php

declare(strict_types=1);

// =============================================================================
// test_db_directo.php — Prueba Exhaustiva de Conexión, 4 Hosts Fijos (Hito 16)
//
// Ejecutable por CLI (`php test_db_directo.php`) o por navegador
// (`https://pittsburgh.tourfindy.com/test_db_directo.php?token=<SETUP_TOKEN>`).
//
// ⚠️ ARCHIVO DE DIAGNÓSTICO TEMPORAL — mismo criterio que test_db.php: por
// HTTP requiere SETUP_TOKEN y se autoniega en producción (esta página
// muestra emails/roles reales de `users` y el listado de tablas — dejarla
// abierta sería una fuga evitable, Mandamiento #2). Por CLI no se exige
// token: quien ya tiene acceso a shell/SSH del servidor ya tiene acceso
// directo a `.env` y a la BD — un token adicional no añadiría seguridad
// real ahí, solo fricción (mismo criterio que scripts/seed_admin.php, que
// se bloquea por HTTP pero no por CLI).
//
// Las credenciales SIEMPRE se leen de `.env` en tiempo de ejecución — este
// archivo se sube a Git (a diferencia de `.env`), así que nunca contiene
// el usuario/contraseña reales como texto literal.
// =============================================================================

$esCli = PHP_SAPI === 'cli';

$envPath = __DIR__ . '/.env';
if (!is_readable($envPath)) {
    if ($esCli) {
        fwrite(STDERR, "Error crítico: no se encontró .env en la raíz del proyecto.\n");
        exit(1);
    }
    http_response_code(500);
    exit('Error crítico: no se encontró .env en la raíz del proyecto.');
}
$env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];

if (!$esCli) {
    if (($env['APP_ENV'] ?? '') === 'production') {
        http_response_code(403);
        exit('Deshabilitado en producción.');
    }

    $setupToken    = (string) ($env['SETUP_TOKEN'] ?? '');
    $providedToken = (string) ($_GET['token'] ?? '');

    if ($setupToken === '' || $providedToken === '' || !hash_equals($setupToken, $providedToken)) {
        http_response_code(403);
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Exhaustiva de Conexión — Acceso Requerido</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<main class="container">
    <h1>Prueba Exhaustiva de Conexión</h1>
    <div class="card">
        <p>Esta página requiere el token de arranque configurado en <code>SETUP_TOKEN</code> (<code>.env</code>).</p>
        <p>Agrega <code>?token=&lt;SETUP_TOKEN&gt;</code> a esta misma URL.</p>
    </div>
</main>
</body>
</html>
        <?php
        exit;
    }
}

$dbName = (string) ($env['DB_NAME'] ?? '');
$dbUser = (string) ($env['DB_USER'] ?? '');
$dbPass = (string) ($env['DB_PASS'] ?? '');

$emailsEsperados = ['dacadomx@yahoo.com', 'armandocastillejos086@gmail.com'];

// Los 4 hosts fijos pedidos, en este orden exacto.
$hosts = ['localhost', '127.0.0.1', 'chir205.websitehostserver.net', '99.198.97.118'];

$guia = [
    'red'            => 'Timeout de socket / firewall — el puerto 3306 no respondió en 3 segundos.',
    'credenciales'   => 'Usuario o contraseña de MySQL incorrectos (Access Denied — SQLSTATE 1045/1044).',
    'bd_inexistente' => 'La base de datos indicada en DB_NAME no existe en este servidor MySQL (SQLSTATE 1049).',
    'desconocido'    => 'Error de conexión no clasificado — revisa el error_log del servidor.',
];

function pp_categorizar(string $mensaje): string
{
    if (preg_match('/\[(\d+)\]/', $mensaje, $m)) {
        return match ($m[1]) {
            '2002', '2003' => 'red',
            '1045', '1044' => 'credenciales',
            '1049'          => 'bd_inexistente',
            default         => 'desconocido',
        };
    }
    return 'desconocido';
}

$resultados = [];

foreach ($hosts as $host) {
    $inicio = microtime(true);
    $fila   = ['host' => $host, 'exito' => false];

    // "localhost" resuelve a socket Unix local en el driver MySQL — un
    // chequeo TCP crudo a :3306 no sería representativo de esa ruta.
    if ($host !== 'localhost') {
        $fp = @fsockopen($host, 3306, $errno, $errstr, 3);
        if ($fp === false) {
            $fila['categoria'] = 'red';
            $fila['detalle']   = "{$errno}: {$errstr}";
            $resultados[] = $fila;
            continue;
        }
        fclose($fp);
    }

    try {
        $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT          => 3,
        ]);

        $fila['ms']     = round((microtime(true) - $inicio) * 1000, 1);
        $fila['tablas'] = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $fila['usuarios'] = in_array('users', $fila['tablas'], true)
            ? $pdo->query('SELECT id, email, role, estatus FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC)
            : [];
        $fila['exito'] = true;
    } catch (PDOException $e) {
        error_log('[' . date('Y-m-d H:i:s') . "] [test_db_directo] host={$host} " . $e->getMessage());
        $fila['categoria'] = pp_categorizar($e->getMessage());
        $fila['detalle']   = 'Ver error_log del servidor para el mensaje completo de PDO.';
    }

    $resultados[] = $fila;
}

// ── SALIDA POR CLI (texto plano) ─────────────────────────────────────────────
if ($esCli) {
    foreach ($resultados as $r) {
        echo str_repeat('=', 70) . "\n";
        echo "HOST: {$r['host']}\n";
        if ($r['exito']) {
            echo "  ESTADO: OK ({$r['ms']} ms)\n";
            echo '  TABLAS (' . count($r['tablas']) . '): ' . implode(', ', $r['tablas']) . "\n";
            echo '  USUARIOS (' . count($r['usuarios']) . "):\n";
            foreach ($r['usuarios'] as $u) {
                echo "    - #{$u['id']} {$u['email']} | rol={$u['role']} | estatus={$u['estatus']}\n";
            }
            $emailsEncontrados = array_column($r['usuarios'], 'email');
            foreach ($emailsEsperados as $esperado) {
                $marca = in_array($esperado, $emailsEncontrados, true) ? 'OK' : 'FALTA';
                echo "    [{$marca}] {$esperado}\n";
            }
        } else {
            echo "  ESTADO: FALLO\n";
            echo "  Categoría: {$r['categoria']}\n";
            echo "  Detalle:   {$r['detalle']}\n";
            echo '  Guía:      ' . $guia[$r['categoria']] . "\n";
        }
    }
    echo str_repeat('=', 70) . "\n";
    exit(0);
}

// ── SALIDA POR NAVEGADOR (HTML) ──────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Exhaustiva de Conexión — PinturaPittsburgh</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<main class="container">
    <h1>Prueba Exhaustiva de Conexión (4 hosts fijos)</h1>

    <?php foreach ($resultados as $r): ?>
        <?php if ($r['exito']): ?>
            <div class="card diag-panel--success">
                <span class="diag-panel__badge">Éxito</span>
                <h2>Host: <?php echo htmlspecialchars($r['host'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p>Tiempo de respuesta: <strong><?php echo htmlspecialchars((string) $r['ms'], ENT_QUOTES, 'UTF-8'); ?> ms</strong></p>
                <p><?php echo count($r['tablas']); ?> tabla(s): <code><?php echo htmlspecialchars(implode(', ', $r['tablas']), ENT_QUOTES, 'UTF-8'); ?></code></p>
                <?php if ($r['usuarios'] !== []): ?>
                    <?php $emailsEncontrados = array_column($r['usuarios'], 'email'); ?>
                    <?php foreach ($emailsEsperados as $esperado): $encontrado = in_array($esperado, $emailsEncontrados, true); ?>
                        <p><?php echo $encontrado ? '✅' : '❌'; ?> <code><?php echo htmlspecialchars($esperado, ENT_QUOTES, 'UTF-8'); ?></code></p>
                    <?php endforeach; ?>
                    <div class="table-scroll">
                        <table class="admin-table">
                            <thead><tr><th>ID</th><th>Email</th><th>Rol</th><th>Estatus</th></tr></thead>
                            <tbody>
                            <?php foreach ($r['usuarios'] as $u): ?>
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
                    <p>La tabla <code>users</code> no existe todavía en este host.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card diag-panel--error">
                <span class="diag-panel__badge">Fallo</span>
                <h2>Host: <?php echo htmlspecialchars($r['host'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($r['categoria'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Detalle:</strong> <?php echo htmlspecialchars($r['detalle'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Guía:</strong> <?php echo htmlspecialchars($guia[$r['categoria']], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</main>
</body>
</html>
