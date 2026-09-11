<?php

declare(strict_types=1);

// =============================================================================
// api/pedido_crear.php — Alta de Pedido (Checkout) — Contrato 5 estricto
// Endpoint: POST /api/pedido_crear.php
// Auth: Público (checkout de cliente final)
//
// Reglas de Piedra (knowledge/03_CONTRATOS_API_Y_RUTAS.md — Contrato 5):
//   1. 422 si modalidad=entrega_domicilio y cp_entrega no está cubierto —
//      SIEMPRE se revalida aquí server-side, nunca se confía en la validación
//      del cliente hecha vía api/validar_cp.php antes del submit.
//   2. precio_unitario es un snapshot del precio vigente al momento del
//      pedido — nunca referencia el precio actual de producto_presentaciones
//      a futuro.
//   3. Transacción atómica: si falla cualquier renglón, se revierte el
//      pedido completo.
//
// Extensión de ingeniería no contemplada literalmente en el contrato original
// pero necesaria para un checkout real: decremento atómico de stock
// (UPDATE ... WHERE stock >= cantidad) para evitar sobreventa bajo
// concurrencia. Sin esto, dos compradores simultáneos podrían agotar el
// mismo stock sin que ninguna de las dos peticiones lo detecte.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'pedido_crear.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (!str_contains($contentType, 'application/json')) {
    send_error('Content-Type debe ser application/json.', 415);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$clienteNombre    = sanitize_string((string) ($payload['cliente_nombre'] ?? ''), 150);
$clienteTelefono  = sanitize_string((string) ($payload['cliente_telefono'] ?? ''), 20);
$clienteEmailRaw  = (string) ($payload['cliente_email'] ?? '');
$clienteEmail     = $clienteEmailRaw !== '' ? sanitize_email($clienteEmailRaw) : null;
$modalidad        = sanitize_string((string) ($payload['modalidad'] ?? ''), 30);
$cpEntrega        = sanitize_string((string) ($payload['cp_entrega'] ?? ''), 5);
$direccionEntrega = sanitize_string((string) ($payload['direccion_entrega'] ?? ''), 255);
$items            = $payload['items'] ?? null;

if ($clienteNombre === '') {
    send_error('El campo "cliente_nombre" es requerido.', 422);
}
if ($clienteTelefono === '') {
    send_error('El campo "cliente_telefono" es requerido.', 422);
}
if ($clienteEmailRaw !== '' && $clienteEmail === '') {
    send_error('El campo "cliente_email" no tiene un formato válido.', 422);
}
if (!in_array($modalidad, ['entrega_domicilio', 'recoleccion_tienda'], true)) {
    send_error('El campo "modalidad" debe ser "entrega_domicilio" o "recoleccion_tienda".', 422);
}
if (!is_array($items) || $items === []) {
    send_error('El campo "items" debe ser un arreglo no vacío.', 422);
}

$itemsLimpios = [];
foreach ($items as $item) {
    $presentacionId = sanitize_int($item['producto_presentacion_id'] ?? 0, 0);
    $cantidad       = sanitize_int($item['cantidad'] ?? 0, 0);

    if ($presentacionId <= 0 || $cantidad <= 0) {
        send_error('Cada item requiere "producto_presentacion_id" y "cantidad" (enteros positivos).', 422);
    }

    $itemsLimpios[] = ['producto_presentacion_id' => $presentacionId, 'cantidad' => $cantidad];
}

if ($modalidad === 'entrega_domicilio') {
    if (!preg_match('/^\d{5}$/', $cpEntrega)) {
        send_error('El campo "cp_entrega" debe tener 5 dígitos cuando modalidad=entrega_domicilio.', 422);
    }
    if ($direccionEntrega === '') {
        send_error('El campo "direccion_entrega" es requerido cuando modalidad=entrega_domicilio.', 422);
    }
}

try {
    $pdo = (new Database())->getConnection();

    // ── Regla de Piedra #1: revalidación server-side de cobertura postal ────
    // Nunca se confía en que el cliente ya validó con api/validar_cp.php.
    if ($modalidad === 'entrega_domicilio') {
        $stmtCp = $pdo->prepare(
            'SELECT activo FROM codigos_postales_cobertura WHERE codigo_postal = :cp LIMIT 1'
        );
        $stmtCp->execute([':cp' => $cpEntrega]);
        $filaCp = $stmtCp->fetch(\PDO::FETCH_ASSOC);

        if ($filaCp === false || (int) $filaCp['activo'] !== 1) {
            send_error('El código postal indicado está fuera de la cobertura de entrega a domicilio en La Paz, B.C.S.', 422);
        }
    }

    $pdo->beginTransaction();

    $stmtPresentacion = $pdo->prepare(
        'SELECT pp.precio, pp.stock, p.nombre
         FROM producto_presentaciones pp
         INNER JOIN productos p ON p.id = pp.producto_id
         WHERE pp.id = :id
         FOR UPDATE'
    );
    $stmtDecrementar = $pdo->prepare(
        'UPDATE producto_presentaciones SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad2'
    );

    $itemsConPrecio = [];
    $total          = 0.0;

    foreach ($itemsLimpios as $item) {
        $stmtPresentacion->execute([':id' => $item['producto_presentacion_id']]);
        $presentacion = $stmtPresentacion->fetch(\PDO::FETCH_ASSOC);

        if ($presentacion === false) {
            $pdo->rollBack();
            send_error('La presentación de producto #' . $item['producto_presentacion_id'] . ' no existe.', 422);
        }

        // Decremento atómico — evita sobreventa bajo peticiones concurrentes.
        $stmtDecrementar->execute([
            ':cantidad'  => $item['cantidad'],
            ':id'        => $item['producto_presentacion_id'],
            ':cantidad2' => $item['cantidad'],
        ]);

        if ($stmtDecrementar->rowCount() === 0) {
            $pdo->rollBack();
            send_error('Stock insuficiente para "' . $presentacion['nombre'] . '".', 422);
        }

        $precioUnitario = (float) $presentacion['precio'];
        $itemsConPrecio[] = [
            'producto_presentacion_id' => $item['producto_presentacion_id'],
            'cantidad'                 => $item['cantidad'],
            'precio_unitario'          => $precioUnitario,
        ];
        $total += $precioUnitario * $item['cantidad'];
    }

    $stmtPedido = $pdo->prepare(
        'INSERT INTO pedidos
            (cliente_nombre, cliente_telefono, cliente_email, cp_entrega, direccion_entrega, modalidad, estatus, total)
         VALUES
            (:cliente_nombre, :cliente_telefono, :cliente_email, :cp_entrega, :direccion_entrega, :modalidad, \'pendiente\', :total)'
    );
    $stmtPedido->execute([
        ':cliente_nombre'    => $clienteNombre,
        ':cliente_telefono'  => $clienteTelefono,
        ':cliente_email'     => $clienteEmail ?: null,
        ':cp_entrega'        => $modalidad === 'entrega_domicilio' ? $cpEntrega : null,
        ':direccion_entrega' => $modalidad === 'entrega_domicilio' ? $direccionEntrega : null,
        ':modalidad'         => $modalidad,
        ':total'             => $total,
    ]);
    $pedidoId = (int) $pdo->lastInsertId();

    $stmtItem = $pdo->prepare(
        'INSERT INTO pedido_items (pedido_id, producto_presentacion_id, cantidad, precio_unitario)
         VALUES (:pedido_id, :producto_presentacion_id, :cantidad, :precio_unitario)'
    );
    foreach ($itemsConPrecio as $item) {
        $stmtItem->execute([
            ':pedido_id'                => $pedidoId,
            ':producto_presentacion_id' => $item['producto_presentacion_id'],
            ':cantidad'                 => $item['cantidad'],
            ':precio_unitario'          => $item['precio_unitario'],
        ]);
    }

    $pdo->commit();

    asfl_log('RESPONSE', ['endpoint' => 'pedido_crear.php', 'pedido_id' => $pedidoId, 'total' => $total]);

    send_success('Pedido creado.', ['id' => $pedidoId, 'total' => $total, 'estatus' => 'pendiente'], 201);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[' . date('Y-m-d H:i:s') . '] [pedido_crear] ' . $e->getMessage());
    send_error('Error interno al procesar el pedido.', 500);
}
