<?php

declare(strict_types=1);

// =============================================================================
// scripts/seed_admin.php — Aprovisionamiento de usuarios (admin/staff/colaborador)
// Uso: php scripts/seed_admin.php [email] [password] [role]
//
// - Si se omite [email], usa SMTP_USER de .env (cuenta institucional ya
//   configurada) como identificador de acceso.
// - Si se omite [password], genera una contraseña aleatoria criptográfica de
//   16 caracteres — NUNCA se hardcodea una contraseña de fábrica adivinable
//   (Mandamiento #2: Seguridad Nivel Militar).
// - Si se omite [role], usa 'admin' (compatibilidad con el uso original).
//   Valores válidos: admin | staff | colaborador (deben existir ya en el
//   ENUM de users.role — ver database/002_banners_cupones_colaborador.sql).
// - Idempotente: si el email ya existe en `users`, actualiza su password_hash
//   y su role/estatus='activo' en vez de duplicar filas.
// - Solo CLI — bloqueado por HTTP en .htaccess (defensa doble, igual que
//   workers/instagram_worker.php).
//
// Requisito: la tabla `users` debe existir ya en la BD (database/001_schema_
// inicial.sql ejecutado contra el servidor). Si no existe, falla con un error
// claro en vez de intentar crearla (Mandamiento #9 — Inmutabilidad del Sistema:
// este script NUNCA emite CREATE TABLE).
// =============================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta por CLI, nunca por HTTP.');
}

require_once __DIR__ . '/../api/conexion.php';
require_once __DIR__ . '/../helpers/password_policy.php'; // generar_password_segura() — Hito 22, única fuente de verdad

function leer_env(): array
{
    $envPath = dirname(__DIR__) . '/.env';
    if (!is_readable($envPath)) {
        fwrite(STDERR, "Error: no existe .env en la raíz del proyecto.\n");
        exit(1);
    }

    return parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
}

$env = leer_env();

$rolesValidos = ['admin', 'staff', 'colaborador'];

$email    = $argv[1] ?? ($env['SMTP_USER'] ?? '');
$password = $argv[2] ?? null;
$role     = $argv[3] ?? 'admin';

if ($email === '') {
    fwrite(STDERR, "Error: no se recibió email y SMTP_USER no está definido en .env.\n");
    fwrite(STDERR, "Uso: php scripts/seed_admin.php <email> [password] [role]\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Error: '{$email}' no es un correo válido.\n");
    exit(1);
}

if (!in_array($role, $rolesValidos, true)) {
    fwrite(STDERR, "Error: role debe ser uno de: " . implode(', ', $rolesValidos) . "\n");
    exit(1);
}

$passwordGenerada = false;
if ($password === null) {
    $password         = generar_password_segura();
    $passwordGenerada = true;
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $existente = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($existente !== false) {
        $update = $pdo->prepare(
            "UPDATE users SET password_hash = :hash, role = :role, estatus = 'activo' WHERE id = :id"
        );
        $update->execute([':hash' => $passwordHash, ':role' => $role, ':id' => $existente['id']]);
        $accion = 'actualizado';
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO users (email, password_hash, role, estatus) VALUES (:email, :hash, :role, 'activo')"
        );
        $insert->execute([':email' => $email, ':hash' => $passwordHash, ':role' => $role]);
        $accion = 'creado';
    }
} catch (\PDOException $e) {
    fwrite(STDERR, "Error de base de datos: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Verifica que database/001_schema_inicial.sql ya se ejecutó contra este servidor.\n");
    exit(1);
}

echo "==============================================================\n";
echo " USUARIO {$accion} correctamente\n";
echo "==============================================================\n";
echo "  Email:      {$email}\n";
echo "  Rol:        {$role}\n";
if ($passwordGenerada) {
    echo "  Password:   {$password}   (generada automáticamente)\n";
    echo "\n  ⚠ ANOTA ESTA CONTRASEÑA AHORA — no se guarda en texto plano en\n";
    echo "  ningún lado y no se puede recuperar. Cámbiala en el primer login\n";
    echo "  en cuanto exista un módulo de gestión de contraseñas.\n";
} else {
    echo "  Password:   (la que proporcionaste por argumento — no se re-imprime)\n";
}
echo "==============================================================\n";
echo "Ingresa en: admin/login.php\n";
