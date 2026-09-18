<?php

declare(strict_types=1);

// =============================================================================
// api/propuesta_autorizar.php — Registro de Autorización/Firma de Cotización
// Endpoint: POST /api/propuesta_autorizar.php
// Auth: Público (documento comercial firmado por el cliente, sin sesión)
// Depende de: tabla `acadep_cotizacion_autorizaciones`
// (database/005_propuesta_autorizaciones.sql)
//
// Contexto: Propuesta/index.html (cotización ACADEP -> Famza) tenía checkbox
// de autorización + firma dibujada en <canvas>, pero nada quedaba guardado del
// lado del servidor — si el cliente firmaba, no había ninguna forma de saberlo.
// Este endpoint guarda el registro y notifica por correo a ACADEP.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'propuesta_autorizar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

$raw     = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    send_error('Cuerpo de la solicitud inválido.', 400);
}

$nombreAutoriza    = sanitize_string((string) ($payload['nombre_autoriza'] ?? ''), 160);
$fechaAutorizacion = sanitize_string((string) ($payload['fecha_autorizacion'] ?? ''), 10);
$cotizacionNumero  = sanitize_string((string) ($payload['cotizacion_numero'] ?? 'COT-ACADEP-2026-001'), 40);
$autorizaDesarrollo = !empty($payload['autoriza_desarrollo']);
$autorizaIguala      = !empty($payload['autoriza_iguala']);
$firmaPng            = (string) ($payload['firma_png'] ?? '');

if ($nombreAutoriza === '') {
    send_error('El nombre de quien autoriza es obligatorio.', 422);
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaAutorizacion)) {
    send_error('La fecha de autorización no es válida.', 422);
}

if (!$autorizaDesarrollo && !$autorizaIguala) {
    send_error('Debes autorizar al menos uno de los dos servicios.', 422);
}

// La firma debe ser un PNG en base64 (lo que produce canvas.toDataURL()) y no
// puede exceder ~2 MB decodificado — suficiente para un trazo de firma real,
// evita que el endpoint reciba archivos arbitrarios enormes.
if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $firmaPng, $m)) {
    send_error('La firma no tiene un formato válido.', 422);
}
$firmaDecodificada = base64_decode($m[1], true);
if ($firmaDecodificada === false || strlen($firmaDecodificada) > 2 * 1024 * 1024) {
    send_error('La firma no es válida o excede el tamaño permitido.', 422);
}

$ipHash        = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare(
        'INSERT INTO acadep_cotizacion_autorizaciones
            (cotizacion_numero, autoriza_desarrollo, autoriza_iguala, nombre_autoriza,
             fecha_autorizacion, firma_png, ip_hash, user_agent_hash)
         VALUES
            (:cotizacion_numero, :autoriza_desarrollo, :autoriza_iguala, :nombre_autoriza,
             :fecha_autorizacion, :firma_png, :ip_hash, :user_agent_hash)'
    );
    $stmt->execute([
        ':cotizacion_numero'   => $cotizacionNumero,
        ':autoriza_desarrollo' => $autorizaDesarrollo ? 1 : 0,
        ':autoriza_iguala'     => $autorizaIguala ? 1 : 0,
        ':nombre_autoriza'     => $nombreAutoriza,
        ':fecha_autorizacion'  => $fechaAutorizacion,
        ':firma_png'           => $firmaPng,
        ':ip_hash'             => $ipHash,
        ':user_agent_hash'     => $userAgentHash,
    ]);

    $idNuevo = (int) $pdo->lastInsertId();

    $correoEnviado = notificarAutorizacionPorCorreo(
        $idNuevo,
        $cotizacionNumero,
        $nombreAutoriza,
        $fechaAutorizacion,
        $autorizaDesarrollo,
        $autorizaIguala,
        $firmaPng
    );

    asfl_log('INFO', [
        'endpoint'       => 'propuesta_autorizar.php',
        'accion'         => 'autorizacion_registrada',
        'id'             => $idNuevo,
        'correo_enviado' => $correoEnviado,
    ]);

    send_success('Autorización registrada correctamente.', [
        'id'             => $idNuevo,
        'correo_enviado' => $correoEnviado,
    ], 201);
} catch (Throwable $e) {
    asfl_log('ERROR', ['endpoint' => 'propuesta_autorizar.php', 'error' => $e->getMessage()]);
    send_error('No se pudo registrar la autorización. Intenta de nuevo.', 500);
}

/**
 * Envía un correo (best-effort — un fallo aquí NUNCA debe tumbar el registro
 * ya guardado en BD, que es la fuente de verdad) a las direcciones de ACADEP
 * con el detalle de la autorización y la firma incrustada como imagen.
 */
function notificarAutorizacionPorCorreo(
    int $id,
    string $cotizacionNumero,
    string $nombreAutoriza,
    string $fechaAutorizacion,
    bool $autorizaDesarrollo,
    bool $autorizaIguala,
    string $firmaPng
): bool {
    $destinatarios = ['dacadomx@gmail.com', 'leolageacadep@gmail.com'];
    $asunto        = "Cotización {$cotizacionNumero} autorizada por Famza";

    $servicios = [];
    if ($autorizaDesarrollo) {
        $servicios[] = 'Costo de Desarrollo de la Plataforma ($17,400.00 MXN, pago único)';
    }
    if ($autorizaIguala) {
        $servicios[] = 'Iguala Mensual de Sistema y Seguridad ($1,392.00 MXN / mes)';
    }
    $listaServicios = '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $servicios)) . '</li></ul>';

    $cid = 'firma_' . $id;
    $cuerpo = '<div style="font-family: system-ui, sans-serif; font-size: 15px; color: #0f172a;">'
        . '<p><strong>' . htmlspecialchars($nombreAutoriza) . '</strong> autorizó la cotización '
        . htmlspecialchars($cotizacionNumero) . ' el ' . htmlspecialchars($fechaAutorizacion) . '.</p>'
        . '<p>Servicios autorizados:</p>' . $listaServicios
        . '<p>Firma capturada:</p>'
        . '<img src="' . htmlspecialchars($firmaPng) . '" alt="Firma" style="max-width:400px;border:1px solid #ccc;">'
        . '<p style="color:#64748b;font-size:12px;">Registro #' . $id . ' — guardado en la base de datos.</p>'
        . '</div>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: ACADEP Cotizaciones <cotizaciones@pittsburgh.tourfindy.com>\r\n";

    $enviados = 0;
    foreach ($destinatarios as $para) {
        if (@mail($para, $asunto, $cuerpo, $headers)) {
            $enviados++;
        }
    }

    return $enviados > 0;
}
