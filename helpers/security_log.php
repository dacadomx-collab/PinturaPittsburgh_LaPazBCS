<?php

declare(strict_types=1);

// =============================================================================
// helpers/security_log.php — Bitácora de Seguridad (Módulo 01, Hito 22)
// Inserta en `log_actividad` (append-only vía triggers — ver
// database/003_modulo01_login_seguridad.sql). Nunca persiste IP/User-Agent en
// claro, siempre su hash SHA-256, ya sea que el evento tenga usuario
// identificado o no (intentos con correo inexistente quedan con
// usuario_id=NULL a propósito — Módulo 01 §2.2, anti-enumeración).
// =============================================================================

require_once __DIR__ . '/../api/jwt.php';

function registrarEventoSeguridad(PDO $pdo, ?int $usuarioId, string $evento, ?string $detalle = null): void
{
    $ipHash     = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $deviceHash = jwtMakeDeviceId();

    $stmt = $pdo->prepare(
        'INSERT INTO `log_actividad` (`usuario_id`, `evento`, `ip_hash`, `device_hash`, `detalle`) '
        . 'VALUES (:usuario_id, :evento, :ip_hash, :device_hash, :detalle)'
    );
    $stmt->execute([
        ':usuario_id'  => $usuarioId,
        ':evento'      => $evento,
        ':ip_hash'     => $ipHash,
        ':device_hash' => $deviceHash,
        ':detalle'     => $detalle,
    ]);
}
