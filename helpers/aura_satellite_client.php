<?php

declare(strict_types=1);

// =============================================================================
// helpers/aura_satellite_client.php — Cliente M2M hacia el servidor Linux
// central AURA (Hito 25, transcrito de
// modulos/MOD_CONEXION_SATELLITE_AURA_M2M.md §5.4 — molde agnóstico del
// holding, sembrado aquí sin alterar su lógica).
//
// El navegador del cliente/admin NUNCA llama directamente a AURA — solo este
// satélite PHP del lado del servidor firma la petición con X-AURA-KEY y la
// reenvía. Autenticación por llave estática, protocolo de fallback LAN → WAN
// → WAN-por-IP (§2 del blueprint), timeouts estrictos (conexión ≤3s, lectura
// ≤8s). Cero dependencias externas, cURL nativo.
//
// Config real de este proyecto (Hito 25) vive en .env: AURA_BASE_URL,
// AURA_GATEWAY_ENDPOINT, AURA_KEY, AURA_TENANT, AURA_FALLBACK_URL — ver
// .env.example y api/asistente_ia.php::dispatchViaAura().
//
// Nota de honestidad (igual que documenta el propio blueprint §6): este
// molde define el CANAL de transporte, no garantiza la estabilidad del
// servidor AURA en sí — no se ha validado en vivo contra un servidor AURA
// real en este proyecto todavía (sin AURA_KEY real disponible en este Hito).
// =============================================================================

final class AuraSatelliteClient
{
    private const LAN_CONNECT_TIMEOUT = 3;
    private const READ_TIMEOUT        = 8;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $gatewayEndpoint,
        private readonly string $apiKey,
        private readonly string $tenant,
        private readonly ?string $fallbackUrl = null,
        private readonly ?string $fallbackIp = null,
    ) {
    }

    /** Construye una instancia desde config ya resuelta (ej. desde .env) — nunca lee .env por sí mismo. */
    public static function fromConfig(array $config): self
    {
        return new self(
            baseUrl: (string) ($config['base_url'] ?? ''),
            gatewayEndpoint: (string) ($config['gateway_endpoint'] ?? ''),
            apiKey: (string) ($config['api_key'] ?? ''),
            tenant: (string) ($config['tenant'] ?? ''),
            fallbackUrl: $config['fallback_url'] ?? null,
            fallbackIp: $config['fallback_ip'] ?? null,
        );
    }

    /**
     * Despacha un prompt. Intenta el host LAN primero; ante un fallo de
     * CONEXIÓN (no de lectura — ver nota abajo) cae una vez al host WAN. Si
     * el fallo WAN es específicamente de resolución DNS y hay una IP directa
     * configurada, un tercer intento fija la resolución DNS a esa IP vía
     * CURLOPT_RESOLVE. Nunca lanza excepciones — siempre devuelve un array
     * de resultado que el llamador puede renderizar o degradar.
     *
     * Un timeout de conexión es seguro de reintentar (el socket nunca llegó
     * a abrirse). Un timeout de LECTURA es ambiguo (el servidor pudo haber
     * procesado la petición sin que el cliente lo sepa) — nunca se reintenta
     * automáticamente, se reporta como error del canal que lo sufrió.
     */
    public function dispatch(string $agentId, string $sessionId, string $prompt): array
    {
        // Payload de chat ultra-liviano a propósito (Protocolo de Contexto
        // Persistente M2M) — nunca reanexar aquí un system prompt completo.
        return $this->dispatchPayload([
            'agent_id'     => $agentId,
            'user_session' => $sessionId,
            'prompt'       => $prompt,
        ]);
    }

    /**
     * Onboarding administrativo de contexto — llamado manualmente, nunca en
     * el camino de despacho de chat. Contrato provisional según el blueprint
     * (sin confirmación oficial de AURA) — no usado todavía en este proyecto.
     */
    public function syncTenantContext(string $agentId, string $systemPrompt): array
    {
        return $this->dispatchPayload([
            'action'        => 'sync_context',
            'agent_id'      => $agentId,
            'tenant'        => $this->tenant,
            'system_prompt' => $systemPrompt,
        ]);
    }

    /** Cascada compartida LAN → WAN → WAN-por-IP — dispatch()/syncTenantContext() delegan aquí. */
    private function dispatchPayload(array $payload): array
    {
        if ($this->apiKey === '' || $this->baseUrl === '') {
            return $this->result(success: false, channel: 'none', errorMessage: 'AURA client not configured (missing base URL or API key).');
        }

        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $lanResult = $this->attempt($this->baseUrl . $this->gatewayEndpoint, $body, 'lan');

        if (!$lanResult['connectFailed'] || $this->fallbackUrl === null || $this->fallbackUrl === '') {
            return $lanResult['raw'];
        }

        $wanUrl    = $this->fallbackUrl . $this->gatewayEndpoint;
        $wanResult = $this->attempt($wanUrl, $body, 'wan', readTimeout: self::READ_TIMEOUT + 4);

        if (!$wanResult['dnsFailed'] || $this->fallbackIp === null || $this->fallbackIp === '') {
            return $wanResult['raw'];
        }

        // Tercer escalón: el dominio WAN no resolvió, pero hay una IP pública
        // conocida configurada para ese mismo servidor.
        $wanIpResult = $this->attempt($wanUrl, $body, 'wan_ip', readTimeout: self::READ_TIMEOUT + 4, resolveIp: $this->fallbackIp);
        return $wanIpResult['raw'];
    }

    /** @return array{connectFailed: bool, dnsFailed: bool, raw: array} */
    private function attempt(string $url, string $body, string $channel, ?int $readTimeout = null, ?string $resolveIp = null): array
    {
        $start = microtime(true);

        $ch   = curl_init($url);
        $opts = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::LAN_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => $readTimeout ?? self::READ_TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-AURA-KEY: ' . $this->apiKey,
            ],
        ];

        if ($resolveIp !== null) {
            $parts = parse_url($url);
            $host  = $parts['host'] ?? '';
            $port  = $parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80);
            if ($host !== '') {
                // Fija la resolución DNS solo para esta petición — preserva
                // Host/SNI para que TLS valide contra el certificado real del
                // hostname, a diferencia de conectar directo a "https://{ip}/...".
                $opts[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $resolveIp];
            }
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $networkLatencyMs = (int) round((microtime(true) - $start) * 1000);

        // CURLE_COULDNT_CONNECT (7) / CURLE_COULDNT_RESOLVE_HOST (6) durante
        // la conexión son los únicos fallos seguros de reintentar en el canal
        // alterno. dnsFailed acota exactamente el caso DNS, que es lo que
        // habilita el tercer escalón por IP directa.
        $dnsFailed     = $errno === CURLE_COULDNT_RESOLVE_HOST;
        $connectFailed = $dnsFailed || $errno === CURLE_COULDNT_CONNECT;

        if ($errno !== 0) {
            return [
                'connectFailed' => $connectFailed,
                'dnsFailed'     => $dnsFailed,
                'raw' => $this->result(
                    success: false,
                    channel: $channel,
                    networkLatencyMs: $networkLatencyMs,
                    httpCode: 0,
                    errorMessage: 'Network error (curl ' . $errno . ') on ' . $channel . ' channel.',
                ),
            ];
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            return [
                'connectFailed' => false,
                'dnsFailed'     => false,
                'raw' => $this->result(
                    success: false,
                    channel: $channel,
                    networkLatencyMs: $networkLatencyMs,
                    httpCode: $httpCode,
                    errorMessage: 'Non-JSON response from ' . $channel . ' channel (HTTP ' . $httpCode . ').',
                ),
            ];
        }

        if ($httpCode === 401 || $httpCode === 403) {
            return [
                'connectFailed' => false,
                'dnsFailed'     => false,
                'raw' => $this->result(
                    success: false,
                    channel: $channel,
                    networkLatencyMs: $networkLatencyMs,
                    httpCode: $httpCode,
                    errorMessage: (string) ($decoded['message'] ?? 'Unauthorized — check API key / tenant.'),
                ),
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'connectFailed' => false,
                'dnsFailed'     => false,
                'raw' => $this->result(
                    success: false,
                    channel: $channel,
                    networkLatencyMs: $networkLatencyMs,
                    httpCode: $httpCode,
                    errorMessage: (string) ($decoded['message'] ?? ('AURA responded with HTTP ' . $httpCode)),
                ),
            ];
        }

        // Acepta tanto un payload plano como uno envuelto bajo "data" (nota
        // de compatibilidad del blueprint, confirmada en validación real
        // contra un servidor AURA — se prefiere "data" cuando existe).
        $payloadOut = is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;

        return [
            'connectFailed' => false,
            'dnsFailed'     => false,
            'raw' => $this->result(
                success: (($decoded['status'] ?? '') === 'success'),
                channel: $channel,
                networkLatencyMs: $networkLatencyMs,
                httpCode: $httpCode,
                response: $payloadOut['response'] ?? null,
                engine: $payloadOut['engine'] ?? null,
                model: $payloadOut['model'] ?? null,
                reportedLatencyMs: isset($payloadOut['latencyMs']) ? (int) $payloadOut['latencyMs'] : null,
                tokensUsed: isset($payloadOut['tokensUsed']) ? (int) $payloadOut['tokensUsed'] : null,
                tokensRemaining: isset($payloadOut['tokensRemaining']) ? (int) $payloadOut['tokensRemaining'] : null,
                sessionId: $payloadOut['sessionId'] ?? null,
                tenantName: $payloadOut['tenantName'] ?? null,
                errorMessage: ($decoded['status'] ?? '') === 'error' ? (string) ($decoded['message'] ?? 'AURA returned status=error.') : null,
            ),
        ];
    }

    private function result(
        bool $success,
        string $channel,
        ?int $networkLatencyMs = null,
        int $httpCode = 0,
        ?string $response = null,
        ?string $engine = null,
        ?string $model = null,
        ?int $reportedLatencyMs = null,
        ?int $tokensUsed = null,
        ?int $tokensRemaining = null,
        ?string $sessionId = null,
        ?string $tenantName = null,
        ?string $errorMessage = null,
    ): array {
        return [
            'success'           => $success,
            'httpCode'          => $httpCode,
            'channelUsed'       => $channel,
            'networkLatencyMs'  => $networkLatencyMs,
            'reportedLatencyMs' => $reportedLatencyMs,
            'response'          => $response,
            'engine'            => $engine,
            'model'             => $model,
            'tokensUsed'        => $tokensUsed,
            'tokensRemaining'   => $tokensRemaining,
            'sessionId'         => $sessionId,
            'tenantName'        => $tenantName,
            'errorMessage'      => $errorMessage,
        ];
    }
}
