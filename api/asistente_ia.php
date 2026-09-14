<?php

declare(strict_types=1);

// =============================================================================
// api/asistente_ia.php — Asistente de Contenido IA (uso interno/administrativo)
// Endpoint: POST /api/asistente_ia.php
// Contrato: knowledge/03_CONTRATOS_API_Y_RUTAS.md — Contrato 7
// Auth: Bearer JWT + role=admin (Mandamiento #14)
//
// Restricciones (knowledge/06_NUCLEO_COGNITIVO_Y_PROMPTS.md §5):
//   - Nunca inventa precios/rendimientos — el prompt instruye al modelo a
//     remitir esos datos a la ficha técnica oficial.
//   - Nunca se expone a clientes finales; solo panel admin.
//   - Nunca se auto-publica: el resultado se devuelve para revisión humana,
//     esta función NO escribe en `publicaciones_sociales`.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/auth_middleware.php'; // expone $authPayload
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

requireRole(ROLE_LEVEL_ADMIN, $authPayload);

$requestStartedAt = microtime(true);
asfl_log('REQUEST', ['endpoint' => 'asistente_ia.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$tipo              = sanitize_string((string) ($payload['tipo'] ?? ''), 30);
$productoId        = sanitize_int($payload['producto_id'] ?? 0, 0);
$plataforma        = sanitize_string((string) ($payload['plataforma'] ?? ''), 20);
$arquetipoObjetivo = sanitize_string((string) ($payload['arquetipo_objetivo'] ?? ''), 40);

$tiposValidos = ['copy_publicitario', 'metadatos_seo', 'recomendacion_tecnica'];
if (!in_array($tipo, $tiposValidos, true)) {
    send_error('El campo "tipo" debe ser uno de: ' . implode(', ', $tiposValidos) . '.', 422);
}
if ($productoId <= 0) {
    send_error('El campo "producto_id" es requerido.', 422);
}
if ($tipo === 'copy_publicitario' && !in_array($plataforma, ['facebook', 'instagram', 'web'], true)) {
    send_error('Para "copy_publicitario", "plataforma" debe ser facebook, instagram o web.', 422);
}

// ── PLANTILLAS DE PROMPT PRECONFIGURADAS (Directiva 3.4) ─────────────────────
// Ninguna plantilla inventa datos técnicos: siempre remiten al producto real
// leído de la BD y piden al modelo verificar cifras contra la ficha técnica.
const ARQUETIPO_LABELS = [
    'pequeno_contratista'  => 'un Pequeño Contratista o Remodelador que valora la rapidez de despacho y el cubrimiento en una mano',
    'gran_contratista'     => 'un Gran Contratista General que exige apego a especificaciones y descuentos escalonados',
    'administrador_fincas' => 'un Administrador de Inmuebles que necesita alta lavabilidad y consistencia de color entre repintes',
    'arquitecto'           => 'un Arquitecto o Especificador que valora certificaciones y datos de colorimetría confiables',
    'pintor'               => 'un pintor profesional que evalúa rendimiento real por litro y tiempos de secado',
];

function promptCopyPublicitario(string $plataforma, string $arquetipoDesc, array $producto): string
{
    $base = "Producto: \"{$producto['nombre']}\" (línea " . ucfirst(str_replace('_', ' ', (string) $producto['linea'])) . ", sustrato {$producto['sustrato']}, brillo {$producto['grado_brillo']}). "
        . "Dirigido a {$arquetipoDesc}. Menciona su resistencia al salitre y/o a la radiación UV del clima de La Paz, B.C.S. cuando sea relevante. "
        . "No inventes precios, porcentajes de rendimiento ni tiempos de secado — si el dato no está aquí, omítelo o remite a la ficha técnica oficial.";

    return match ($plataforma) {
        'facebook'  => "Escribe una publicación de Facebook en tono consultivo, resaltando relación costo-beneficio y rendimiento volumétrico, máximo 700 caracteres. {$base}",
        'instagram' => "Escribe un copy conciso para Instagram enfocado en impacto visual, con 3 hashtags de relevancia municipal para La Paz B.C.S., máximo 220 caracteres. {$base}",
        default     => "Escribe una descripción de ficha de producto para el sitio web, tono técnico-consultivo orientado a resolver patologías constructivas costeras. {$base}",
    };
}

function promptMetadatosSeo(array $producto): string
{
    return "Genera un <title> (máximo 60 caracteres) y un <meta name=\"description\"> (máximo 155 caracteres) "
        . "optimizados para búsquedas locales en La Paz, B.C.S., para el producto \"{$producto['nombre']}\" "
        . "(línea " . ucfirst(str_replace('_', ' ', (string) $producto['linea'])) . "). Incluye \"Distribuidor Autorizado\" si cabe de forma natural. "
        . "No inventes certificaciones, premios ni datos técnicos que no te proporcioné.";
}

function promptRecomendacionTecnica(array $producto): string
{
    return "Redacta una recomendación de aplicación de máximo 120 palabras para \"{$producto['nombre']}\" "
        . "sobre sustrato {$producto['sustrato']} en el clima costero de La Paz, B.C.S. (radiación UV extrema, salinidad marina). "
        . "Incluye: horario recomendado de aplicación evitando la hora de máxima insolación, y una advertencia sobre limpiar "
        . "sales solubles antes de imprimar si el sustrato es de mampostería. No inventes tiempos de secado ni rendimientos "
        . "por litro — indica explícitamente que deben verificarse en la ficha técnica oficial del producto.";
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare('SELECT id, nombre, linea, sustrato, grado_brillo FROM productos WHERE id = :id AND activo = 1');
    $stmt->execute([':id' => $productoId]);
    $producto = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($producto === false) {
        send_error('Producto no encontrado o inactivo. Verifica el producto_id contra el catálogo.', 404);
    }

    $arquetipoDesc = ARQUETIPO_LABELS[$arquetipoObjetivo] ?? 'el cliente general de PinturaPittsburgh en La Paz, B.C.S.';

    $prompt = match ($tipo) {
        'copy_publicitario'      => promptCopyPublicitario($plataforma, $arquetipoDesc, $producto),
        'metadatos_seo'          => promptMetadatosSeo($producto),
        'recomendacion_tecnica'  => promptRecomendacionTecnica($producto),
    };

    $resultado = dispatchAiProvider($prompt);

    $durationMs = (int) round((microtime(true) - $requestStartedAt) * 1000);
    asfl_log('RESPONSE', ['endpoint' => 'asistente_ia.php', 'status' => $resultado['ok'] ? 'success' : 'error', 'duracion_ms' => $durationMs]);

    if ($resultado['ok'] === false) {
        error_log('[' . date('Y-m-d H:i:s') . '] [asistente_ia] ' . $resultado['error']);
        send_error('El Asistente de IA no está disponible en este momento. Intenta más tarde.', 502);
    }

    send_success('Contenido generado — revisa antes de publicar.', [
        'contenido_generado' => $resultado['contenido'],
        'modelo_usado'       => $resultado['modelo'],
    ]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [asistente_ia] ' . $e->getMessage());
    send_error('Error interno al generar el contenido.', 500);
}

// ── Dispatcher de proveedor único (sin failover — ver knowledge/06 §2) ───────

/** @return array{ok:bool,contenido?:string,modelo?:string,error?:string} */
function dispatchAiProvider(string $prompt): array
{
    // NOTA: este proyecto lee `.env` con parse_ini_file() (ver api/auth_login.php),
    // NO con getenv() — parse_ini_file() nunca hace putenv().
    $env          = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $anthropicKey = (string) ($env['ANTHROPIC_API_KEY'] ?? '');
    $openaiKey    = (string) ($env['OPENAI_API_KEY'] ?? '');
    $model        = (string) ($env['AI_MODEL'] ?? '');

    if ($anthropicKey !== '') {
        return callAnthropic($prompt, $anthropicKey, $model !== '' ? $model : 'claude-sonnet-5');
    }

    if ($openaiKey !== '') {
        return callOpenAi($prompt, $openaiKey, $model !== '' ? $model : 'gpt-4o-mini');
    }

    return ['ok' => false, 'error' => 'Ninguna API Key de IA configurada (ANTHROPIC_API_KEY / OPENAI_API_KEY en .env).'];
}

/** @return array{ok:bool,contenido?:string,modelo?:string,error?:string} */
function callAnthropic(string $prompt, string $apiKey, string $model): array
{
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model'      => $model,
            'max_tokens' => 700,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ], JSON_THROW_ON_ERROR),
    ]);

    $body     = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'cURL Anthropic: ' . $curlErr];
    }

    $decoded = json_decode($body, true);
    $texto   = $decoded['content'][0]['text'] ?? null;

    if ($httpCode !== 200 || $texto === null) {
        return ['ok' => false, 'error' => 'Anthropic HTTP ' . $httpCode . ': ' . $body];
    }

    return ['ok' => true, 'contenido' => $texto, 'modelo' => $model];
}

/** @return array{ok:bool,contenido?:string,modelo?:string,error?:string} */
function callOpenAi(string $prompt, string $apiKey, string $model): array
{
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ], JSON_THROW_ON_ERROR),
    ]);

    $body     = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'cURL OpenAI: ' . $curlErr];
    }

    $decoded = json_decode($body, true);
    $texto   = $decoded['choices'][0]['message']['content'] ?? null;

    if ($httpCode !== 200 || $texto === null) {
        return ['ok' => false, 'error' => 'OpenAI HTTP ' . $httpCode . ': ' . $body];
    }

    return ['ok' => true, 'contenido' => $texto, 'modelo' => $model];
}
