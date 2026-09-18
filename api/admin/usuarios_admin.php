<?php

declare(strict_types=1);

// =============================================================================
// api/admin/usuarios_admin.php — Controlador Central de Usuarios (Contrato 15,
// Hito 23, Módulo 01 §9.5)
// GET → lista todos los usuarios (id, email, role, estatus, bloqueado, created_at).
// PUT/POST → mutación por `action`: cambiar_rol | cambiar_estatus | resetear_password.
// Auth: Bearer JWT + role=admin (nivel 100), igual que el resto de api/admin/*.
//
// Regla de auto-protección: el admin autenticado no puede mutar su propia
// cuenta desde este panel — evita autosuspenderse o autodegradar su propio
// rol y quedar fuera del panel sin querer.
//
// Toda mutación fuerza `sesion_invalidada_en = NOW()` (api/auth_middleware.php
// ya la revisa en cada request autenticada, Hito 22): un cambio de rol o
// estatus no debe esperar a que expire el access token vigente (hasta 15
// min) — el usuario afectado queda obligado a iniciar sesión de nuevo, y su
// próximo JWT ya reflejará el rol/estatus real.
// =============================================================================

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../auth_middleware.php'; // expone $authPayload
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/input_sanitizer.php';
require_once __DIR__ . '/../../helpers/password_policy.php';

requireRole(ROLE_LEVEL_ADMIN, $authPayload);

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'PUT', 'POST'], true)) {
    send_error('Método no permitido.', 405);
}

try {
    $pdo     = (new Database())->getConnection();
    $adminId = (int) ($authPayload['sub'] ?? 0);

    // ── GET: listado completo ────────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT `id`, `email`, `role`, `estatus`, `created_at`, '
            . '(`bloqueado_hasta` IS NOT NULL AND `bloqueado_hasta` > NOW()) AS `bloqueado` '
            . 'FROM `users` ORDER BY `email` ASC'
        );
        $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($usuarios as &$fila) {
            $fila['bloqueado'] = (bool) $fila['bloqueado'];
        }
        unset($fila);

        send_success('Usuarios listados.', ['usuarios' => $usuarios]);
    }

    // ── PUT/POST: mutación por acción ────────────────────────────────────────
    try {
        $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        send_error('Payload JSON inválido.', 400);
    }

    $accion = sanitize_string((string) ($payload['action'] ?? ''), 30);
    $userId = sanitize_int($payload['id'] ?? null, -1);

    if ($userId < 1) {
        send_error('id de usuario inválido.', 422);
    }

    if ($userId === $adminId) {
        send_error('No puedes modificar tu propia cuenta desde este panel.', 403);
    }

    $existeStmt = $pdo->prepare('SELECT `id` FROM `users` WHERE `id` = :id LIMIT 1');
    $existeStmt->execute([':id' => $userId]);
    if ($existeStmt->fetch() === false) {
        send_error('Usuario no encontrado.', 404);
    }

    switch ($accion) {
        case 'cambiar_rol':
            $rolesValidos = ['admin', 'staff', 'colaborador'];
            $nuevoRol     = sanitize_string((string) ($payload['role'] ?? ''), 20);

            if (!in_array($nuevoRol, $rolesValidos, true)) {
                send_error('role debe ser admin, staff o colaborador.', 422);
            }

            $update = $pdo->prepare(
                'UPDATE `users` SET `role` = :role, `sesion_invalidada_en` = NOW() WHERE `id` = :id'
            );
            $update->execute([':role' => $nuevoRol, ':id' => $userId]);

            send_success('Rol actualizado.', ['id' => $userId, 'role' => $nuevoRol]);
            break;

        case 'cambiar_estatus':
            $estatusValidos = ['activo', 'inactivo'];
            $nuevoEstatus   = sanitize_string((string) ($payload['estatus'] ?? ''), 20);

            if (!in_array($nuevoEstatus, $estatusValidos, true)) {
                send_error('estatus debe ser activo o inactivo.', 422);
            }

            $update = $pdo->prepare(
                'UPDATE `users` SET `estatus` = :estatus, `sesion_invalidada_en` = NOW() WHERE `id` = :id'
            );
            $update->execute([':estatus' => $nuevoEstatus, ':id' => $userId]);

            send_success('Estatus actualizado.', ['id' => $userId, 'estatus' => $nuevoEstatus]);
            break;

        case 'resetear_password':
            $nuevaPassword = generar_password_segura();
            $hash          = password_hash($nuevaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            $update = $pdo->prepare(
                'UPDATE `users` SET `password_hash` = :hash, `sesion_invalidada_en` = NOW(), '
                . '`intentos_fallidos` = 0, `bloqueado_hasta` = NULL WHERE `id` = :id'
            );
            $update->execute([':hash' => $hash, ':id' => $userId]);

            send_success('Contraseña restablecida. Cópiala ahora, no se volverá a mostrar.', [
                'id'                => $userId,
                'password_temporal' => $nuevaPassword,
            ]);
            break;

        default:
            send_error('action debe ser cambiar_rol, cambiar_estatus o resetear_password.', 422);
    }
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [usuarios_admin] ' . $e->getMessage());
    send_error('Error interno al procesar la solicitud.', 500);
}
