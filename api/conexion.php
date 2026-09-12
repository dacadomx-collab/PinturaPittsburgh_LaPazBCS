<?php

declare(strict_types=1);

// =============================================================================
// api/conexion.php — Conexión PDO Centralizada (AXON_DCD Security Standard)
// Mandamiento #11: Arranque Blindado — TODA conexión pasa por aquí.
// Mandamiento #12: Bóveda de Secretos — Lee credenciales SOLO desde .env
// Mandamiento #13 / REGLA CERO: Aislamiento de Entornos — en LOCAL (XAMPP,
// APP_ENV=local) la BD NUNCA cae a 'localhost'/'127.0.0.1' si el host
// remoto falla: eso significaría leer/escribir silenciosamente en el MySQL
// de XAMPP en vez de reportar el error real. En STAGING/PRODUCCIÓN sí se
// activa ese fallback (Hito 15) — dentro de la misma cuenta de hosting,
// 'localhost' es la ruta correcta (socket Unix local, sin firewall
// perimetral), ver getConnection()/hostsDeFallback() más abajo y
// knowledge/04_ARQUITECTURA_Y_BLINDAJE.md §5.
// =============================================================================

class Database
{
    /**
     * Host remoto centralizado de respaldo si DB_HOST falta en .env (Regla Cero).
     * NUNCA hardcodear aquí un hostname real de hosting — definir el valor real
     * únicamente en `.env` (Mandamiento #12: Bóveda de Secretos). Este placeholder
     * fuerza un fallo visible en vez de conectar silenciosamente a un host
     * heredado de otro proyecto.
     */
    private const DEFAULT_REMOTE_DB_HOST = '[HOST_BD_REMOTO_DEL_HOSTING]';

    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $allowed_origins;
    private string $appEnv;
    public ?PDO $conn = null;

    public function __construct()
    {
        $env = $this->loadEnv(__DIR__ . '/../.env');

        $this->host            = (string) ($env['DB_HOST'] ?? self::DEFAULT_REMOTE_DB_HOST);
        $this->db_name         = (string) ($env['DB_NAME'] ?? '');
        $this->username        = (string) ($env['DB_USER'] ?? '');
        $this->password        = (string) ($env['DB_PASS'] ?? '');
        $this->allowed_origins = (string) ($env['ALLOWED_ORIGINS'] ?? '');
        $this->appEnv          = (string) ($env['APP_ENV'] ?? 'local');
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private function jsonError(string $message, int $httpCode = 500): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => $message, 'data' => []]);
        exit;
    }

    private function loadEnv(string $path): array
    {
        if (!is_readable($path)) {
            $this->jsonError('Error crítico de servidor: Configuración no encontrada.');
        }
        $data = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($data === false) {
            $this->jsonError('Error crítico de servidor: Formato de configuración inválido.');
        }
        return $data;
    }

    // ── CORS (opcional — usar api/cors.php para control granular por endpoint) ─

    public function setCorsHeaders(): void
    {
        $origin      = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedList = array_map('trim', explode(',', $this->allowed_origins));

        // Origen desconocido → no emitir header, el navegador bloquea solo
        if (!empty($origin) && !in_array($origin, $allowedList, true)) {
            $this->jsonError('Acceso denegado: Origen no autorizado.', 403);
        }

        if (in_array($origin, $allowedList, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } else {
            // Fallback para herramientas de desarrollo sin HTTP_ORIGIN (Postman, local)
            header('Access-Control-Allow-Origin: ' . ($allowedList[0] ?? '*'));
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Vary: Origin');
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    // ── CONEXIÓN PDO ──────────────────────────────────────────────────────────

    /**
     * Hosts de fallback cuando el DB_HOST configurado falla por red (SQLSTATE
     * 2002/2003 — timeout o rechazo de conexión, ej. errno 10060). Nunca se
     * activa si APP_ENV=local (Regla Cero, api/conexion.php §doc de cabecera):
     * un desarrollador local con MySQL de XAMPP corriendo en su propia máquina
     * NUNCA debe caer silenciosamente en esa BD ajena solo porque el host
     * remoto no respondió — eso sería peor que el error original (datos
     * inconsistentes sin ningún aviso). En staging/producción sí tiene
     * sentido: dentro de la misma cuenta de hosting, MySQL habla por socket
     * Unix local vía "localhost" (PDO/mysqlnd lo resuelve automáticamente al
     * socket configurado en PHP — nunca se hardcodea una ruta de socket como
     * /var/lib/mysql/mysql.sock, que varía entre proveedores y sería menos
     * portable, no más).
     */
    private function hostsDeFallback(): array
    {
        if (($this->appEnv ?: 'local') === 'local') {
            return [];
        }
        return ['localhost', '127.0.0.1'];
    }

    public function getConnection(): PDO
    {
        if (empty($this->db_name) || empty($this->username)) {
            $this->jsonError('Error de BD: credenciales incompletas.');
        }

        $hosts = array_unique(array_merge([$this->host], $this->hostsDeFallback()));
        $ultimaExcepcion = null;

        foreach ($hosts as $host) {
            try {
                $dsn        = "mysql:host={$host};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Previene SQL Injection
                    PDO::ATTR_TIMEOUT            => 3, // No congelar la app 30s si el host no responde
                ]);

                if ($host !== $this->host) {
                    error_log('[' . date('Y-m-d H:i:s') . "] [Database::getConnection] Fallback activado: '{$this->host}' no respondió, conectado vía '{$host}'.");
                }

                return $this->conn;
            } catch (PDOException $e) {
                $ultimaExcepcion = $e;
                // Intenta el siguiente host de la lista, si queda alguno.
            }
        }

        // NUNCA exponer el mensaje real de PDO al frontend
        error_log('[' . date('Y-m-d H:i:s') . '] [Database::getConnection] ' . ($ultimaExcepcion?->getMessage() ?? 'Sin excepción capturada.'));
        $this->jsonError('Error de conexión a la base de datos. Intente más tarde.');
    }
}
