<?php

declare(strict_types=1);

// =============================================================================
// helpers/seo_helper.php — Motor de Metadatos y Marcado Estructurado (Hito 24,
// adaptado de modulos/MODULO_04_MARKETING_ORGANICO.md §1 — "Motor de SEO y
// Visibilidad Hiperlocal")
//
// Descartado del blueprint original (§2 Ingeniería del AdServer, §3
// Ciberseguridad Perimetral Anti-Fraude Publicitario): ese contenido es un
// AdServer B2B White-Label para una plataforma de medios que vende espacio
// publicitario a patrocinadores (viewability MRC, click-fraud, rate limiting
// de telemetría de banners) — no existe ese modelo de negocio en
// Famza (e-commerce hiperlocal de pinturas, sin inventario
// publicitario propio). Se conserva únicamente §1: metadatos on-page,
// sitemap dinámico y JSON-LD — adaptado al dominio real de este proyecto.
//
// Nota de precisión (Mandamiento #4, Anti-Alucinación): el blueprint pide un
// tipo de schema.org "PaintStore" — ese tipo NO EXISTE en el vocabulario real
// de schema.org. Se usa `HardwareStore` (subtipo real de LocalBusiness/Store,
// la categoría más cercana para un distribuidor de pinturas/recubrimientos)
// en su lugar, para producir JSON-LD válido y verificable en Google Rich
// Results Test. `telephone`/`openingHours` se omiten a propósito: no hay un
// dato real y verificado en el Codex — nunca se inventa un teléfono u horario
// (mismo principio que nunca inventar una contraseña o API Key).
// =============================================================================

/**
 * URL base pública del sitio (para URLs absolutas en OG/JSON-LD/sitemap).
 * FRONTEND_URL apunta al dominio público real (staging hoy, producción
 * cuando se registre) incluso en el .env local — Hito 18: el propósito de
 * ese .env es desarrollar contra datos reales, pero el frontend que un
 * crawler de Facebook/WhatsApp puede alcanzar siempre es el dominio público,
 * nunca localhost.
 */
function seoUrlBase(): string
{
    $env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $url = (string) ($env['FRONTEND_URL'] ?? $env['APP_URL'] ?? '');

    return rtrim($url, '/');
}

/** Trunca respetando palabras completas — nunca corta a media palabra. */
function seoTruncar(string $texto, int $max): string
{
    $texto = trim($texto);
    if (mb_strlen($texto) <= $max) {
        return $texto;
    }

    $cortado = mb_substr($texto, 0, $max - 1);
    $ultimoEspacio = mb_strrpos($cortado, ' ');
    if ($ultimoEspacio !== false) {
        $cortado = mb_substr($cortado, 0, $ultimoEspacio);
    }

    return rtrim($cortado) . '…';
}

/**
 * JSON-LD institucional (LocalBusiness) — NAP real (Hito 26, corrección de
 * identidad: el cliente real es "Famza — The Colour Boutique", distribuidor
 * autorizado de The Pittsburgh Paints Company en La Paz — "PinturaPittsburgh"
 * fue el nombre de trabajo asumido desde el Hito 1, nunca confirmado con el
 * cliente real hasta la Ficha de Levantamiento de Información, Septiembre
 * 2026). Dirección y coordenadas tomadas del enlace de Google Maps de la
 * sucursal matriz en esa Ficha — ya no son una aproximación de ciudad.
 * Estático: no depende de ningún request, se imprime igual en cada carga de
 * index.html.
 *
 * Pendiente de la Ficha (Mandamiento #4 — nunca se inventa): `telephone` usa
 * solo el fijo de mostrador (confirmado, 10 dígitos); el número de
 * WhatsApp/móvil que dio el cliente tiene un dígito de más ("612 612 157
 * 0249") — probable error de captura, no se usa hasta confirmarlo. Perfiles
 * de redes sociales (Facebook/Instagram) no se agregan como `sameAs` porque
 * la Ficha dejó el campo de URL en blanco — no se adivina la URL a partir
 * del nombre de usuario. Existe una segunda sucursal (Matamoros y
 * Revolución) sin dirección completa verificada todavía — este schema
 * representa solo la matriz (Mariano Abasolo).
 */
function seoLocalBusinessJsonLd(): string
{
    $base = seoUrlBase();

    $datos = [
        '@context'   => 'https://schema.org',
        '@type'      => 'HardwareStore',
        'name'       => 'Famza — The Colour Boutique',
        'image'      => $base . '/assets/img/logo.jpeg',
        'url'        => $base . '/',
        'telephone'  => '+52 612 125 8171',
        'address'    => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Mariano Abasolo 3114, Pueblo Nuevo',
            'postalCode'      => '23060',
            'addressLocality' => 'La Paz',
            'addressRegion'   => 'Baja California Sur',
            'addressCountry'  => 'MX',
        ],
        // Pin real de la sucursal matriz (Google Maps, Ficha de Levantamiento
        // Sept. 2026) — ya no es una aproximación de centro de ciudad.
        'geo'        => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => 24.1486569,
            'longitude' => -110.3282455,
        ],
        'areaServed' => [
            '@type' => 'City',
            'name'  => 'La Paz, Baja California Sur',
        ],
        'brand'      => [
            '@type' => 'Brand',
            'name'  => 'The Pittsburgh Paints Company',
        ],
    ];

    return '<script type="application/ld+json">' . json_encode($datos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * JSON-LD de producto (Product + Offer). $producto debe traer las claves de
 * seoObtenerProductoParaMetadatos(): nombre, descripcion, linea, imagen,
 * precio_desde, disponible.
 */
function seoProductoJsonLd(array $producto, string $urlProducto): string
{
    $datos = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $producto['nombre'],
        'description' => $producto['descripcion'] ?? ('Producto de la línea ' . $producto['linea_legible'] . ' — The Pittsburgh Paints Company, disponible en Famza La Paz.'),
        'image'       => $producto['imagen'],
        'url'         => $urlProducto,
        'brand'       => [
            '@type' => 'Brand',
            'name'  => 'The Pittsburgh Paints Company',
        ],
        'offers'      => [
            '@type'         => 'Offer',
            'url'           => $urlProducto,
            'priceCurrency' => 'MXN',
            'price'         => $producto['precio_desde'],
            'availability'  => $producto['disponible']
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'seller'        => [
                '@type' => 'HardwareStore',
                'name'  => 'Famza — The Colour Boutique',
            ],
        ],
    ];

    return '<script type="application/ld+json">' . json_encode($datos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Bloque de metaetiquetas Open Graph + Twitter Card. $datos: titulo,
 * descripcion, imagen (URL absoluta), url (URL absoluta), tipo ('website'|'product').
 * Límites del Módulo 04 §1: og:title ≤70 car., og:description ≤155 car.
 * Todo valor pasa por htmlspecialchars() — son datos de producto (BD), nunca
 * se inyectan crudos en un atributo HTML (Mandamiento #2).
 */
function seoOpenGraphTags(array $datos): string
{
    $titulo      = seoTruncar((string) $datos['titulo'], 70);
    $descripcion = seoTruncar((string) $datos['descripcion'], 155);
    $imagen      = (string) $datos['imagen'];
    $url         = (string) $datos['url'];
    $tipo        = (string) ($datos['tipo'] ?? 'website');

    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    $tags = [
        '<meta property="og:type" content="' . $e($tipo) . '">',
        '<meta property="og:site_name" content="Famza — The Colour Boutique">',
        '<meta property="og:title" content="' . $e($titulo) . '">',
        '<meta property="og:description" content="' . $e($descripcion) . '">',
        '<meta property="og:image" content="' . $e($imagen) . '">',
        '<meta property="og:url" content="' . $e($url) . '">',
        '<meta property="og:locale" content="es_MX">',
        '<meta name="twitter:card" content="summary_large_image">',
        '<meta name="twitter:title" content="' . $e($titulo) . '">',
        '<meta name="twitter:description" content="' . $e($descripcion) . '">',
        '<meta name="twitter:image" content="' . $e($imagen) . '">',
    ];

    return implode("\n    ", $tags);
}

/**
 * Consulta ligera de un producto SOLO con los campos que necesitan las
 * metaetiquetas/JSON-LD — deliberadamente más angosta que api/catalogo_detalle.php
 * (que trae todas las presentaciones/fichas para la UI interactiva). No
 * duplica ese contrato, sirve un propósito distinto (SSR de metadatos antes
 * del primer byte).
 *
 * @return array{nombre:string, descripcion:?string, linea:string, linea_legible:string, imagen:string, precio_desde:float, disponible:bool}|null
 */
function seoObtenerProductoParaMetadatos(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.nombre, p.descripcion, p.linea, p.can_cut_url, '
        . 'MIN(pp.precio) AS precio_desde, COALESCE(SUM(pp.stock), 0) AS stock_total '
        . 'FROM productos p LEFT JOIN producto_presentaciones pp ON pp.producto_id = p.id '
        . 'WHERE p.id = :id AND p.activo = 1 GROUP BY p.id'
    );
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($fila === false || $fila['nombre'] === null) {
        return null;
    }

    $lineasLegibles = [
        'speedhide'   => 'Speedhide',
        'manor_hall'  => 'Manor Hall',
        'perma_crete' => 'Perma-Crete',
        'pitt_glaze'  => 'Pitt-Glaze',
        'otra'        => 'Otra línea',
    ];

    $base = seoUrlBase();

    return [
        'nombre'        => (string) $fila['nombre'],
        'descripcion'   => $fila['descripcion'] !== null ? (string) $fila['descripcion'] : null,
        'linea'         => (string) $fila['linea'],
        'linea_legible' => $lineasLegibles[$fila['linea']] ?? 'Otra línea',
        // can_cut_url ya es una URL absoluta cuando existe (asset del Marketing
        // Hub); si falta, se usa el logo institucional como respaldo — NO es
        // un placeholder ideal para compartir (SVG pequeño, no 1200x630), se
        // documenta como limitación conocida en el informe de este Hito.
        'imagen'        => $fila['can_cut_url'] !== null && $fila['can_cut_url'] !== ''
            ? (string) $fila['can_cut_url']
            : $base . '/assets/img/logo.svg',
        'precio_desde'  => (float) ($fila['precio_desde'] ?? 0),
        'disponible'    => (int) $fila['stock_total'] > 0,
    ];
}
