<?php

declare(strict_types=1);

// =============================================================================
// test_db.php — Diagnóstico Visual Multi-Host de Base de Datos (Hito 15)
//
// ⚠️ ARCHIVO DE DIAGNÓSTICO TEMPORAL — mismo criterio de blindaje que
// api/setup_diagnostico.php: protegido por SETUP_TOKEN (ya existente en
// .env, sin secreto nuevo que gestionar) y autoniega por completo si
// APP_ENV=production. Se mantiene el token —a diferencia de "sin tokens
// complejos que bloqueen al usuario"— porque esta página muestra en texto
// plano los emails/roles reales de `users` y el listado de tablas de la
// BD: dejarla abierta en una URL pública indexable sería una fuga de
// información evitable (Mandamiento #2, Seguridad Nivel Militar) sin
// ganar nada en usabilidad real, ya que la URL completa con el token ya
// resuelto se entrega directamente al Arquitecto (ver informe de cierre
// de hito) — nunca hay que "gestionar" el token a mano.
//
// Consolidación (Hito 15): reemplaza a test_conexion.php (Hito 14, ahora
// eliminado) — mismo propósito, ampliado a multi-host y con salida visual
// en vez de un solo intento de conexión. Un solo nombre por concepto
// (Mandamiento #10) — no se mantienen dos herramientas de diagnóstico de
// conexión en paralelo.
//
// Uso: test_db.php?token=<SETUP_TOKEN>
// =============================================================================

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
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos — Acceso Requerido</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<main class="container">
    <h1>Diagnóstico de Base de Datos</h1>
    <div class="card">
        <p>Esta página requiere el token de arranque configurado en <code>SETUP_TOKEN</code> (<code>.env</code>).</p>
        <p>Agrega <code>?token=&lt;SETUP_TOKEN&gt;</code> a esta misma URL. El Arquitecto ya tiene el enlace completo listo para usar.</p>
    </div>
</main>
</body>
</html>
    <?php
    exit;
}

$dbHost = (string) ($env['DB_HOST'] ?? '');
$dbName = (string) ($env['DB_NAME'] ?? '');
$dbUser = (string) ($env['DB_USER'] ?? '');
$dbPass = (string) ($env['DB_PASS'] ?? '');

$emailsEsperados = ['dacadomx@yahoo.com', 'armandocastillejos086@gmail.com'];

$candidatos = array_values(array_unique(array_filter([$dbHost, 'localhost', '127.0.0.1'])));

$guia = [
    'red'            => 'Timeout de socket / firewall (equivalente a WSAETIMEDOUT / errno 10060). El puerto 3306 no respondió. Si "localhost" también falló, revisa que MySQL esté corriendo en este servidor; si solo falló el host remoto, es el firewall perimetral o el Remote MySQL de cPanel.',
    'credenciales'   => 'Usuario o contraseña de MySQL incorrectos (Access Denied). Verifica DB_USER/DB_PASS en el .env de este servidor contra cPanel → MySQL Databases.',
    'bd_inexistente' => 'La base de datos indicada en DB_NAME no existe en este servidor MySQL. Verifica el nombre exacto (suele llevar prefijo de cuenta) en cPanel → MySQL Databases.',
    'desconocido'    => 'Error de conexión no clasificado — revisa el error_log del servidor para el mensaje completo de PDO.',
];

function pp_test_db_categorizar(string $mensaje): string
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

$resultado = null;
$intentos   = [];

foreach ($candidatos as $host) {
    $inicio = microtime(true);

    // "localhost" resuelve a socket Unix local en el driver MySQL — un
    // chequeo TCP crudo a :3306 no sería representativo de esa ruta, así
    // que solo se hace fsockopen para hosts que sí viajan por TCP.
    if ($host !== 'localhost') {
        $fp = @fsockopen($host, 3306, $errno, $errstr, 3);
        if ($fp === false) {
            $intentos[$host] = ['categoria' => 'red', 'detalle' => "{$errno}: {$errstr}"];
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

        $ms     = round((microtime(true) - $inicio) * 1000, 1);
        $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $usuarios = [];

        if (in_array('users', $tablas, true)) {
            $usuarios = $pdo->query('SELECT id, email, role, estatus FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
        }

        $resultado = [
            'host'      => $host,
            'ms'        => $ms,
            'tablas'    => $tablas,
            'usuarios'  => $usuarios,
        ];
        break;
    } catch (PDOException $e) {
        error_log('[' . date('Y-m-d H:i:s') . "] [test_db] host={$host} " . $e->getMessage());
        $intentos[$host] = ['categoria' => pp_test_db_categorizar($e->getMessage()), 'detalle' => 'Ver error_log del servidor para el mensaje completo.'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos — PinturaPittsburgh</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<main class="container">
    <h1>Diagnóstico Multi-Host de Base de Datos</h1>
    <p class="brand-tagline">Host configurado en <code>.env</code>: <code><?php echo htmlspecialchars($dbHost, ENT_QUOTES, 'UTF-8'); ?></code> — orden de intento: <?php echo htmlspecialchars(implode(' → ', $candidatos), ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if ($resultado !== null): ?>
        <div class="card diag-panel--success">
            <span class="diag-panel__badge">Conexión exitosa</span>
            <h2>Host: <?php echo htmlspecialchars($resultado['host'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Tiempo de respuesta: <strong><?php echo htmlspecialchars((string) $resultado['ms'], ENT_QUOTES, 'UTF-8'); ?> ms</strong></p>
            <p><?php echo count($resultado['tablas']); ?> tabla(s) encontrada(s): <code><?php echo htmlspecialchars(implode(', ', $resultado['tablas']), ENT_QUOTES, 'UTF-8'); ?></code></p>
        </div>

        <?php if ($resultado['usuarios'] !== []): ?>
            <div class="card">
                <h2>Usuarios en <code>users</code> (<?php echo count($resultado['usuarios']); ?>)</h2>
                <?php
                $emailsEncontrados = array_column($resultado['usuarios'], 'email');
                foreach ($emailsEsperados as $esperado):
                    $encontrado = in_array($esperado, $emailsEncontrados, true);
                ?>
                <p><?php echo $encontrado ? '✅' : '❌'; ?> <code><?php echo htmlspecialchars($esperado, ENT_QUOTES, 'UTF-8'); ?></code><?php echo $encontrado ? ' — detectado' : ' — NO detectado'; ?></p>
                <?php endforeach; ?>

                <div class="table-scroll">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Email</th><th>Rol</th><th>Estatus</th></tr></thead>
                        <tbody>
                        <?php foreach ($resultado['usuarios'] as $u): ?>
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
            </div>
        <?php else: ?>
            <div class="card diag-panel--error">
                <span class="diag-panel__badge">Advertencia</span>
                <p>La conexión funcionó pero la tabla <code>users</code> no existe todavía — ejecuta la migración (<code>api/setup_diagnostico.php?action=migrate</code>).</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card diag-panel--error">
            <span class="diag-panel__badge">Conexión fallida en todos los hosts</span>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead><tr><th>Host intentado</th><th>Categoría</th><th>Detalle</th></tr></thead>
                    <tbody>
                    <?php foreach ($intentos as $host => $info): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($info['categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($info['detalle'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php $categoriaPrincipal = $intentos[$dbHost]['categoria'] ?? 'desconocido'; ?>
            <p><strong>Guía técnica (según el host configurado en .env):</strong> <?php echo htmlspecialchars($guia[$categoriaPrincipal], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
