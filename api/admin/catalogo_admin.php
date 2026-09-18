<?php

declare(strict_types=1);

// =============================================================================
// api/admin/catalogo_admin.php — Control de Catálogo y Precios (Directiva 3.1)
// GET  → lista productos + presentaciones (incluye inactivos, a diferencia
//        del catálogo público que solo expone activo=1).
// PUT  → actualiza precio/stock/activo de UNA presentación por su id.
// Auth: Bearer JWT + role=admin (Mandamiento #14)
// Columnas: espejo exacto de database/001_schema_inicial.sql (Directiva 1).
// =============================================================================

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../auth_middleware.php'; // expone $authPayload
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/input_sanitizer.php';

requireRole(ROLE_LEVEL_ADMIN, $authPayload);

$pdo = (new Database())->getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query(
            'SELECT p.id, p.nombre, p.linea, p.sustrato, p.grado_brillo, p.precio_lista,
                    p.precio_minimo_map, p.activo,
                    pp.id AS presentacion_id, pp.volumen, pp.sku, pp.precio, pp.stock
             FROM productos p
             LEFT JOIN producto_presentaciones pp ON pp.producto_id = p.id
             ORDER BY p.id ASC, pp.volumen ASC'
        );
        $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        send_success('Catálogo administrativo.', ['filas' => $filas]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $presentacionId = sanitize_int($payload['presentacion_id'] ?? 0, 0);
        if ($presentacionId <= 0) {
            send_error('El campo "presentacion_id" es requerido.', 422);
        }

        $campos = [];
        $params = [':id' => $presentacionId];

        if (isset($payload['precio'])) {
            $precio = (float) $payload['precio'];
            if ($precio < 0) {
                send_error('El precio no puede ser negativo.', 422);
            }
            $campos[]        = 'precio = :precio';
            $params[':precio'] = $precio;
        }

        if (isset($payload['stock'])) {
            $stock = sanitize_int($payload['stock'], -1);
            if ($stock < 0) {
                send_error('El stock debe ser un entero mayor o igual a 0.', 422);
            }
            $campos[]        = 'stock = :stock';
            $params[':stock'] = $stock;
        }

        if ($campos === []) {
            send_error('No se recibió ningún campo para actualizar (precio y/o stock).', 422);
        }

        $sql  = 'UPDATE producto_presentaciones SET ' . implode(', ', $campos) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            send_error('Presentación no encontrada.', 404);
        }

        send_success('Presentación actualizada.', ['presentacion_id' => $presentacionId]);
    }

    send_error('Método no permitido.', 405);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [catalogo_admin] ' . $e->getMessage());
    send_error('Error interno al procesar el catálogo.', 500);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}
