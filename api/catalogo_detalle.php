<?php

declare(strict_types=1);

// =============================================================================
// api/catalogo_detalle.php — Ficha de Producto Individual (Contrato 3, extensión)
// Endpoint: GET /api/catalogo_detalle.php?id=1
// Auth: Público
//
// El Contrato 3 original (knowledge/03_CONTRATOS_API_Y_RUTAS.md) ya mencionaba
// `api/catalogo_detalle.php` desde el Hito 2 pero solo se implementó
// `catalogo_listar.php`. Este endpoint completa esa promesa para dar soporte
// real a producto.html (PDP) — mismo blindaje que catalogo_listar.php.
//
// PROHIBIDO exponer `precio_minimo_map` — no se selecciona en el SQL.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'catalogo_detalle.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

$id = sanitize_int($_GET['id'] ?? 0, 0);

if ($id <= 0) {
    send_error('El parámetro "id" es requerido y debe ser un entero positivo.', 422);
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare(
        'SELECT p.id, p.nombre, p.linea, p.sustrato, p.grado_brillo, p.propiedades_funcionales,
                p.descripcion, p.can_cut_url, p.ficha_tecnica_pdf_url, p.ficha_seguridad_pdf_url,
                pp.id AS presentacion_id, pp.volumen, pp.sku, pp.precio, pp.stock
         FROM productos p
         LEFT JOIN producto_presentaciones pp ON pp.producto_id = p.id
         WHERE p.id = :id AND p.activo = 1
         ORDER BY pp.volumen ASC'
    );
    $stmt->execute([':id' => $id]);
    $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if ($filas === []) {
        send_error('Producto no encontrado o no disponible.', 404);
    }

    $primera = $filas[0];
    $producto = [
        'id'                      => (int) $primera['id'],
        'nombre'                  => $primera['nombre'],
        'linea'                   => $primera['linea'],
        'sustrato'                => $primera['sustrato'],
        'grado_brillo'            => $primera['grado_brillo'],
        'propiedades_funcionales' => $primera['propiedades_funcionales'] !== null
            ? json_decode((string) $primera['propiedades_funcionales'], true)
            : [],
        'descripcion'             => $primera['descripcion'],
        'can_cut_url'             => $primera['can_cut_url'],
        'ficha_tecnica_pdf_url'   => $primera['ficha_tecnica_pdf_url'],
        'ficha_seguridad_pdf_url' => $primera['ficha_seguridad_pdf_url'],
        'presentaciones'          => [],
    ];

    foreach ($filas as $fila) {
        if ($fila['presentacion_id'] !== null) {
            $producto['presentaciones'][] = [
                'id'      => (int) $fila['presentacion_id'],
                'volumen' => $fila['volumen'],
                'sku'     => $fila['sku'],
                'precio'  => (float) $fila['precio'],
                'stock'   => (int) $fila['stock'],
            ];
        }
    }

    asfl_log('RESPONSE', ['endpoint' => 'catalogo_detalle.php', 'id' => $id, 'encontrado' => true]);

    send_success('Detalle de producto.', ['producto' => $producto]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [catalogo_detalle] ' . $e->getMessage());
    send_error('Error interno al consultar el producto.', 500);
}
