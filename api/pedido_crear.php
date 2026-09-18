<?php

declare(strict_types=1);

// =============================================================================
// api/pedido_crear.php — Contrato 5, RETIRADO (Hito 27)
// Directiva del cliente real (Famza — The Colour Boutique, 2026-09-14,
// documentada por Rafael en collab/rafa): el sitio público es informativo,
// no se hacen ventas en línea — la compra ocurre en tienda física. Se
// conserva la ruta con 410 (en vez de eliminarla) para responder de forma
// explícita a cualquier cliente/integración antigua que aún la invoque, sin
// abrir conexión a BD ni tocar existencias.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../helpers/response.php';

send_error('La compra en línea no está disponible. Contacta a Famza para recibir atención en tienda.', 410);
