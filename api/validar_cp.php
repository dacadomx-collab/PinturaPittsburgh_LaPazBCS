<?php

declare(strict_types=1);

// =============================================================================
// api/validar_cp.php — Validador de Cobertura Postal (Directiva Hito 3, Tarea 2)
// Endpoint: GET /api/validar_cp.php?cp=23000
// Contrato: knowledge/03_CONTRATOS_API_Y_RUTAS.md — Contrato 4
// Auth: Público (no modifica datos — Mandamiento #14 no aplica aquí)
//
// Fail-safe: CP inexistente o activo=0 responde 200 con cubierto:false. Nunca
// se abre falsamente una entrega fuera de la lista blanca de La Paz, B.C.S.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'validar_cp.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

// Admite ?cp=23000 (uso normal) o, si el cliente lo envía como payload JSON
// sobre un GET (caso excepcional contemplado en el contrato), lo lee también.
$cp = $_GET['cp'] ?? null;
if ($cp === null) {
    $rawBody = file_get_contents('php://input');
    if ($rawBody !== false && $rawBody !== '') {
        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            $cp = $decoded['cp'] ?? null;
        } catch (\JsonException) {
            $cp = null;
        }
    }
}

$cp = sanitize_string((string) ($cp ?? ''), 5);

if (!preg_match('/^\d{5}$/', $cp)) {
    send_error('El código postal debe tener exactamente 5 dígitos numéricos.', 422);
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare(
        'SELECT zona_colonia, modalidad_logistica, ventana_entrega, activo
         FROM codigos_postales_cobertura
         WHERE codigo_postal = :cp
         LIMIT 1'
    );
    $stmt->execute([':cp' => $cp]);
    $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

    asfl_log('RESPONSE', ['endpoint' => 'validar_cp.php', 'cp' => $cp, 'encontrado' => $fila !== false]);

    if ($fila === false || (int) $fila['activo'] !== 1) {
        // Fail-safe: no existe en la lista blanca o fue desactivado.
        send_success('Consulta de cobertura postal.', ['cubierto' => false]);
    }

    send_success('Consulta de cobertura postal.', [
        'cubierto'            => true,
        'zona_colonia'        => $fila['zona_colonia'],
        'modalidad_logistica' => $fila['modalidad_logistica'],
        'ventana_entrega'     => $fila['ventana_entrega'],
    ]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [validar_cp] ' . $e->getMessage());
    send_error('Error interno al validar el código postal.', 500);
}
