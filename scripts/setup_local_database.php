<?php

declare(strict_types=1);

// Alta explícita de una BD nueva en la instancia aislada de local_database.sh.
// Excepción local autorizada por el usuario; nunca usa configuración remota.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$envPath = $root . '/.env';
if (file_exists($envPath)) {
    fwrite(STDERR, "Ya existe .env. No se sobrescribe ni se modifica la base existente.\n");
    exit(1);
}
$socket = $root . '/logs/local-mariadb/server.sock';
try {
    $pdo = new PDO('mysql:unix_socket=' . $socket . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $expectedDir = realpath($root . '/logs/local-mariadb/data');
    $actualDir = realpath((string) $pdo->query('SELECT @@datadir')->fetchColumn());
    if (!$expectedDir || $actualDir !== $expectedDir) throw new RuntimeException('La instancia no corresponde a este proyecto.');
    $pdo->exec('CREATE DATABASE pinturapittsburgh_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE pinturapittsburgh_local');
    foreach (['001_schema_inicial.sql', '002_banners_cupones_colaborador.sql', '003_seed_local.sql'] as $file) {
        $sql = file_get_contents($root . '/database/' . $file);
        if ($sql === false) throw new RuntimeException('No se pudo leer ' . $file);
        $pdo->exec($sql);
    }
    $pdo->exec("CREATE USER 'pp_local'@'127.0.0.1' IDENTIFIED BY ''");
    $pdo->exec('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES ON pinturapittsburgh_local.* TO \'pp_local\'@\'127.0.0.1\'');
    // Comprobar exactamente las credenciales que utilizará la API antes de escribir .env.
    new PDO('mysql:host=127.0.0.1;port=3307;dbname=pinturapittsburgh_local;charset=utf8mb4', 'pp_local', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $secret = base64_encode(random_bytes(64));
    $env = <<<INI
; Solo pruebas locales. Autorización explícita del usuario; no copiar a staging.
APP_ENV = "local"
APP_URL = "http://127.0.0.1:8000"
APP_DEBUG = "true"
DB_HOST = "127.0.0.1"
DB_PORT = "3307"
DB_NAME = "pinturapittsburgh_local"
DB_USER = "pp_local"
DB_PASS = ""
JWT_SECRET = "$secret"
JWT_ACCESS_TTL = "900"
JWT_REFRESH_TTL = "2592000"
JWT_TTL = "3600"
ALLOWED_ORIGINS = "http://127.0.0.1:8000,http://localhost:8000"
FRONTEND_URL = "http://127.0.0.1:8000"
POSTAL_VALIDATION_ENABLED = "true"
INI;
    $oldMask = umask(0077);
    $handle = fopen($envPath, 'x');
    if (!$handle) throw new RuntimeException('No se pudo crear .env.');
    fwrite($handle, $env . PHP_EOL);
    fclose($handle);
    umask($oldMask);
    echo "Base local creada: 10 tablas, 3 banners, 2 promociones, 3 publicaciones y 4 productos de prueba.\n";
    echo "Usuario pp_local sin contraseña, limitado a esta base en 127.0.0.1:3307.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Configuración local incompleta: " . $e->getMessage() . "\nNo se borran datos automáticamente.\n");
    exit(1);
}
