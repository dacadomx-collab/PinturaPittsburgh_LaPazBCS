<?php

declare(strict_types=1);

// =============================================================================
// api/catalogo_listar.php — Catálogo Público (Directiva Hito 3, Tarea 2)
// Endpoint: GET /api/catalogo_listar.php?sustrato=&grado_brillo=&linea=&page=
// Contrato: knowledge/03_CONTRATOS_API_Y_RUTAS.md — Contrato 3
// Auth: Público
//
// PROHIBIDO exponer `precio_minimo_map` — ni siquiera se incluye en el SELECT
// (dato financiero interno, ver knowledge/05_MATRIZ_FINANCIERA_Y_VENTAS.md §2).
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

const CATALOGO_PAGE_SIZE = 20;

// Únicos valores válidos — deben coincidir EXACTAMENTE con los ENUM de
// `productos` en database/001_schema_inicial.sql (Directiva 1 — anti-1054).
const SUSTRATOS_VALIDOS    = ['concreto_costero', 'enjarre_yeso', 'tabla_roca', 'madera_marina', 'herreria', 'piso_alto_transito'];
const GRADOS_BRILLO_VALIDOS = ['mate', 'eggshell', 'satinado', 'semibrillante', 'brillante'];
const LINEAS_VALIDAS       = ['speedhide', 'manor_hall', 'perma_crete', 'pitt_glaze', 'otra'];

asfl_log('REQUEST', ['endpoint' => 'catalogo_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

$sustrato    = sanitize_string((string) ($_GET['sustrato'] ?? ''), 40);
$gradoBrillo = sanitize_string((string) ($_GET['grado_brillo'] ?? ''), 40);
$linea       = sanitize_string((string) ($_GET['linea'] ?? ''), 20);
$page        = max(1, sanitize_int($_GET['page'] ?? 1, 1));

if ($sustrato !== '' && !in_array($sustrato, SUSTRATOS_VALIDOS, true)) {
    send_error('Valor de "sustrato" no reconocido.', 422);
}
if ($gradoBrillo !== '' && !in_array($gradoBrillo, GRADOS_BRILLO_VALIDOS, true)) {
    send_error('Valor de "grado_brillo" no reconocido.', 422);
}
if ($linea !== '' && !in_array($linea, LINEAS_VALIDAS, true)) {
    send_error('Valor de "linea" no reconocido.', 422);
}

try {
    $pdo = (new Database())->getConnection();

    // ── Construcción de filtros comunes (reutilizados en COUNT y SELECT) ─────
    $condiciones = ['activo = 1'];
    $params      = [];

    if ($sustrato !== '') {
        $condiciones[]      = 'sustrato = :sustrato';
        $params[':sustrato'] = $sustrato;
    }
    if ($gradoBrillo !== '') {
        $condiciones[]         = 'grado_brillo = :grado_brillo';
        $params[':grado_brillo'] = $gradoBrillo;
    }
    if ($linea !== '') {
        $condiciones[]   = 'linea = :linea';
        $params[':linea'] = $linea;
    }

    $whereSql = implode(' AND ', $condiciones);

    // ── Total de productos que cumplen el filtro (para paginación) ──────────
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE {$whereSql}");
    $stmtTotal->execute($params);
    $total = (int) $stmtTotal->fetchColumn();

    // ── Página de IDs de producto (evita duplicados por el JOIN con LIMIT) ───
    $offset = (($page - 1) * CATALOGO_PAGE_SIZE);
    $stmtIds = $pdo->prepare(
        "SELECT id FROM productos WHERE {$whereSql} ORDER BY id ASC LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $key => $value) {
        $stmtIds->bindValue($key, $value, \PDO::PARAM_STR);
    }
    $stmtIds->bindValue(':limit', CATALOGO_PAGE_SIZE, \PDO::PARAM_INT);
    $stmtIds->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmtIds->execute();
    $ids = array_map('intval', $stmtIds->fetchAll(\PDO::FETCH_COLUMN));

    if ($ids === []) {
        asfl_log('RESPONSE', ['endpoint' => 'catalogo_listar.php', 'total' => $total, 'pagina_vacia' => true]);
        send_success('Catálogo público.', ['productos' => [], 'total' => $total]);
    }

    // ── Detalle de productos + presentaciones para los IDs de esta página ────
    // NOTA: precio_minimo_map NUNCA se selecciona en este endpoint (público).
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtDetalle = $pdo->prepare(
        "SELECT p.id, p.nombre, p.linea, p.sustrato, p.grado_brillo, p.can_cut_url,
                pp.id AS presentacion_id, pp.volumen, pp.sku, pp.precio, pp.stock
         FROM productos p
         LEFT JOIN producto_presentaciones pp ON pp.producto_id = p.id
         WHERE p.id IN ({$placeholders})
         ORDER BY p.id ASC, pp.volumen ASC"
    );
    $stmtDetalle->execute($ids);
    $filas = $stmtDetalle->fetchAll(\PDO::FETCH_ASSOC);

    // ── Agrupar filas planas en productos con array de presentaciones ────────
    $productosPorId = [];
    foreach ($filas as $fila) {
        $id = (int) $fila['id'];
        if (!isset($productosPorId[$id])) {
            $productosPorId[$id] = [
                'id'             => $id,
                'nombre'         => $fila['nombre'],
                'linea'          => $fila['linea'],
                'sustrato'       => $fila['sustrato'],
                'grado_brillo'   => $fila['grado_brillo'],
                'can_cut_url'    => $fila['can_cut_url'],
                'presentaciones' => [],
            ];
        }
        if ($fila['presentacion_id'] !== null) {
            $productosPorId[$id]['presentaciones'][] = [
                'volumen' => $fila['volumen'],
                'sku'     => $fila['sku'],
                'precio'  => (float) $fila['precio'],
                'stock'   => (int) $fila['stock'],
            ];
        }
    }

    asfl_log('RESPONSE', ['endpoint' => 'catalogo_listar.php', 'total' => $total, 'en_pagina' => count($productosPorId)]);

    send_success('Catálogo público.', [
        'productos' => array_values($productosPorId),
        'total'     => $total,
    ]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [catalogo_listar] ' . $e->getMessage());
    send_error('Error interno al consultar el catálogo.', 500);
}
