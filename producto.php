<?php

declare(strict_types=1);

// =============================================================================
// producto.php — Ficha de Producto Individual (PDP), migrado desde
// producto.html (Hito 24, Módulo 04 §1 — SEO/metadatos server-side).
//
// El resto de la página (todo lo que sigue después del </head>) NO CAMBIA
// respecto a producto.html — sigue siendo 100% render de cliente vía
// assets/js/producto-page.js, que consulta api/catalogo_detalle.php como
// siempre. Este bloque PHP solo se agrega ANTES del primer byte para inyectar
// <title>/OG/Twitter Card/JSON-LD con los datos REALES del producto — algo
// que el renderizado 100% client-side nunca podía lograr, porque los
// crawlers de WhatsApp/Facebook no ejecutan JavaScript, solo leen el HTML
// inicial que el servidor entrega.
//
// Fallo silencioso y elegante: si `id` falta, no es válido, o el producto no
// existe/está inactivo, se usan las metaetiquetas genéricas del sitio — la
// página sigue funcionando exactamente igual (el JS de cliente ya maneja ese
// caso con su propio estado de error visual, #pdp-error).
// =============================================================================

require_once __DIR__ . '/api/conexion.php';
require_once __DIR__ . '/helpers/seo_helper.php';

$productoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$producto   = null;

if ($productoId > 0) {
    try {
        $pdo      = (new Database())->getConnection();
        $producto = seoObtenerProductoParaMetadatos($pdo, $productoId);
    } catch (\Throwable) {
        // Database::getConnection() ya maneja su propio error/log — si algo
        // falla aquí, la página sigue sirviéndose con metadatos genéricos en
        // vez de romperse (Mandamiento: nunca mostrar errores de PDO/PHP).
        $producto = null;
    }
}

$urlBase     = seoUrlBase();
$urlCanonica = $urlBase . '/producto.php' . ($productoId > 0 ? '?id=' . $productoId : '');

if ($producto !== null) {
    $tituloPagina = $producto['nombre'] . ' — ' . $producto['linea_legible'] . ' | PinturaPittsburgh';
    $descripcion  = $producto['descripcion'] ?? ('Producto de la línea ' . $producto['linea_legible'] . ', The Pittsburgh Paints Company — disponible en PinturaPittsburgh, Distribuidor Autorizado en La Paz, B.C.S.');
    $ogTags       = seoOpenGraphTags([
        'titulo'      => $tituloPagina,
        'descripcion' => $descripcion,
        'imagen'      => $producto['imagen'],
        'url'         => $urlCanonica,
        'tipo'        => 'product',
    ]);
    $jsonLd = seoProductoJsonLd($producto, $urlCanonica);
} else {
    $tituloPagina = 'Producto — PinturaPittsburgh';
    $descripcion  = 'Ficha técnica de producto — PinturaPittsburgh, Distribuidor Autorizado de The Pittsburgh Paints Company en La Paz, B.C.S.';
    $ogTags       = seoOpenGraphTags([
        'titulo'      => $tituloPagina,
        'descripcion' => $descripcion,
        'imagen'      => $urlBase . '/assets/img/logo.svg',
        'url'         => $urlCanonica,
        'tipo'        => 'website',
    ]);
    $jsonLd = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($urlCanonica, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo $ogTags; ?>
    <?php if ($jsonLd !== ''): ?>
    <?php echo $jsonLd; ?>
    <?php endif; ?>
    <link rel="icon" href="favicon.ico">

    <script src="assets/js/theme-init.js"></script>
    <link rel="preload" href="assets/css/main.css" as="style">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <header class="site-header">
        <div class="container site-header__bar">
            <p class="site-header__value-prop">
                <a class="header-back-link" href="index.html">← Volver al catálogo</a>
            </p>
            <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" aria-label="Cambiar entre modo día y modo noche"></button>
        </div>
    </header>

    <main class="container">
        <p id="pdp-error" class="postal-bar__result postal-bar__result--blocked" hidden></p>

        <div id="pdp-contenido" class="arf-grid">
            <section class="card arf-col-2">
                <img id="pdp-media" class="catalog-media catalog-media--pdp" alt="">
            </section>

            <section class="card arf-col-2">
                <span id="pdp-badge" class="linea-badge"></span>
                <h1 id="pdp-nombre"></h1>
                <p id="pdp-meta" class="product-card__meta"></p>
                <p id="pdp-descripcion" class="brand-tagline"></p>

                <div id="pdp-propiedades" class="propiedad-list"></div>

                <h2 class="pdp-subtitulo">Presentaciones</h2>
                <div id="pdp-presentaciones" class="presentacion-list"></div>

                <div class="field-group">
                    <label for="pdp-cantidad">Cantidad</label>
                    <input class="field pdp-cantidad-input" type="number" id="pdp-cantidad" min="1" value="1">
                </div>

                <p class="pdp-precio">Total: <span id="pdp-precio-total"></span></p>

                <button type="button" id="pdp-agregar-btn" class="btn btn--gold">Agregar a mi pedido</button>
                <p id="pdp-carrito-status" class="postal-bar__result" hidden></p>

                <h2 class="pdp-subtitulo">Documentación</h2>
                <div id="pdp-fichas" class="pdp-fichas"></div>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>PinturaPittsburgh - Distribuidor Autorizado La Paz · Bulevar Agustín Olachea e Indeco, La Paz, B.C.S.</p>
        </div>
    </footer>

    <button type="button" id="back-to-top-btn" class="back-to-top-btn" hidden aria-label="Volver arriba">↑</button>

    <script src="assets/js/cart.js" defer></script>
    <script src="assets/js/producto-page.js" defer></script>
    <script src="assets/js/theme-toggle.js" defer></script>
    <script src="assets/js/back-to-top.js" defer></script>
</body>
</html>
