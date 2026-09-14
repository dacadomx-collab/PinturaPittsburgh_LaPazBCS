<?php

declare(strict_types=1);

// =============================================================================
// api/sitemap.php — Generador Dinámico de Sitemap (Hito 24, Módulo 04 §1)
// Se sirve al público en la URL amigable /sitemap.xml vía RewriteRule en
// .htaccess. Estándar sitemaps.org, sin dependencias.
//
// URLs estáticas: solo la home (/) — este proyecto es de página única para
// catálogo/contacto (no existen catalogo.html ni contacto.html como páginas
// separadas; ese contenido vive dentro de index.html). checkout.html se
// excluye a propósito: es una página transaccional sin valor de indexación
// (práctica estándar de SEO — nunca se indexa un carrito/checkout), y además
// el módulo de checkout está temporalmente en pausa mientras Rafael
// consolida el frontend.
//
// URLs dinámicas: una por cada producto activo, apuntando a producto.php?id=.
// Sin capa de caché (a diferencia del "sitemap de noticias, TTL 300s" del
// blueprint original): ese requisito existe para un volumen de cientos de
// artículos/día en una plataforma editorial, no para un catálogo de pinturas
// que cambia con frecuencia semanal/mensual — el query es barato tal cual.
// =============================================================================

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/seo_helper.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = seoUrlBase();

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->query('SELECT id, updated_at FROM productos WHERE activo = 1 ORDER BY id ASC');
    $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [sitemap] ' . $e->getMessage());
    $productos = [];
}

$e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// ── URLs estáticas ───────────────────────────────────────────────────────────
echo '  <url>' . "\n";
echo '    <loc>' . $e($base . '/') . '</loc>' . "\n";
echo '    <changefreq>daily</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// ── URLs dinámicas: catálogo oficial de The Pittsburgh Paints Company ──────
foreach ($productos as $producto) {
    $lastmod = date('Y-m-d', strtotime((string) $producto['updated_at']));

    echo '  <url>' . "\n";
    echo '    <loc>' . $e($base . '/producto.php?id=' . (int) $producto['id']) . '</loc>' . "\n";
    echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
