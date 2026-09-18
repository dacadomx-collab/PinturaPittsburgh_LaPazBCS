-- =============================================================================
-- 003_modulo01_login_seguridad.sql — PinturaPittsburgh_LaPazBCS
-- -----------------------------------------------------------------------------
-- BORRADOR DE PROPUESTA — NO APLICADO A NINGUNA BASE DE DATOS TODAVÍA.
-- Cierra los 4 gaps de MODULO_01_LOGIN_Y_ACCESO.md señalados por el Arquitecto
-- (política de contraseñas, rate limiting, auditoría de login en BD, expiración
-- forzada de sesión) sin tocar ni una línea de PHP. Pendiente de validación
-- explícita del Arquitecto y de su registro en knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md
-- ANTES de ejecutarse contra cualquier entorno (local via túnel SSH o staging).
--
-- Fuente: modulos/MODULO_01_LOGIN_Y_ACCESO.md §1 (schema), §2.2 (anti-fuerza
-- bruta), §7 (motor de política de contraseña) — adaptado de `{{TABLE_PREFIX}}usuarios`
-- a la tabla real `users` de este proyecto (001_schema_inicial.sql), sin
-- renombrar columnas ya en uso por api/auth_login.php / api/auth_middleware.php.
--
-- Deliberadamente NO incluido en este parche (fuera del alcance pedido, para no
-- extender sin autorización — Mandamiento 6):
--   - `recuperacion_password` (Módulo 01 §3.6, flujo de "olvidé mi contraseña")
--   - Columnas `token_acceso` / `token_expira_en` / `device_hash` en `users`
--     (solo aplican a la variante de "token opaco" del Módulo 01 §3; este
--     proyecto ya usa JWT stateless — api/jwt.php — que no requiere persistir
--     tokens ni device hash en la tabla de usuarios)
--   - Columna `hito_cumplido` (onboarding de USUARIOS internos; el onboarding
--     de colaboradores externos ya se resuelve con localStorage — Hito 20 —
--     y no hay un flujo de "primer login" pendiente para admin/staff hoy)
--   - Ascenso de `role` a incluir `super_admin` (la jerarquía numérica del
--     Módulo 01 §6 se implementa en PHP como mapa de constantes, no como
--     cambio de ENUM — con 1 sola tabla `role` activa arriba de staff/colaborador
--     hoy, un ENUM nuevo sería un cambio de schema sin necesidad funcional real)
--   - Columnas de geolocalización en `log_actividad` (`ip_pais`/`ip_estado`/
--     `ip_ciudad` de Módulo 01 §9.3) — pertenecen al flujo avanzado de invitación
--     dual, no al núcleo de login/sesión/acceso pedido en esta fase
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. ALTER `users` — rate limiting (intentos_fallidos/bloqueado_hasta) y
--    expiración forzada de sesión sin abandonar el diseño stateless de JWT
--    (sesion_invalidada_en: si el `iat` del JWT es anterior a esta marca, el
--    middleware lo rechaza aunque la firma/expiración natural sigan siendo
--    válidas — cubre suspensión de cuenta y cambio de contraseña sin necesitar
--    una tabla de tokens revocados).
-- -----------------------------------------------------------------------------
ALTER TABLE `users`
    ADD COLUMN `intentos_fallidos`   SMALLINT UNSIGNED NOT NULL DEFAULT 0        AFTER `estatus`,
    ADD COLUMN `bloqueado_hasta`     DATETIME NULL DEFAULT NULL                   AFTER `intentos_fallidos`,
    ADD COLUMN `sesion_invalidada_en` DATETIME NULL DEFAULT NULL                  AFTER `bloqueado_hasta`;

-- -----------------------------------------------------------------------------
-- 2. `configuracion_seguridad` — fila única con la política de contraseña
--    activa y los umbrales del limitador de intentos fallidos. Lectura pública
--    sin auth (para que el frontend valide en vivo contra la política vigente),
--    mutación reservada a `admin` (Módulo 01 §7).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion_seguridad` (
    `id`                    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `politica_password`     ENUM('simple','media','fuerte') NOT NULL DEFAULT 'media',
    `max_intentos_fallidos` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    `minutos_bloqueo`       SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    `actualizado_en`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `chk_configuracion_seguridad_fila_unica` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion_seguridad` (`id`, `politica_password`, `max_intentos_fallidos`, `minutos_bloqueo`)
VALUES (1, 'media', 5, 15)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- -----------------------------------------------------------------------------
-- 3. `log_actividad` — bitácora de auditoría de acceso, append-only (Módulo 01
--    §1.2/§9.3). `usuario_id` es NULLABLE a propósito: un intento fallido con
--    un correo que no existe no tiene un usuario que referenciar (y así lo
--    exige la mitigación anti-enumeración de §2.2 — nunca revelar por
--    temporización ni por la bitácora si el correo existe o no).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `log_actividad` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`  BIGINT UNSIGNED NULL,
    `evento`      VARCHAR(60)     NOT NULL COMMENT 'ej. login_exitoso, login_fallido, cuenta_bloqueada, sesion_invalidada',
    `ip_hash`     CHAR(64)        NOT NULL COMMENT 'SHA-256 de la IP — nunca se guarda la IP en claro',
    `device_hash` CHAR(64)        NOT NULL COMMENT 'SHA-256 de IP+User-Agent, mismo valor que jwtMakeDeviceId()',
    `detalle`     VARCHAR(255)    NULL,
    `creado_en`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_actividad_usuario` (`usuario_id`),
    KEY `idx_log_actividad_evento_fecha` (`evento`, `creado_en`),
    CONSTRAINT `fk_log_actividad_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `trg_log_actividad_no_update`
BEFORE UPDATE ON `log_actividad`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'log_actividad es append-only: UPDATE prohibido.';
END$$

CREATE TRIGGER `trg_log_actividad_no_delete`
BEFORE DELETE ON `log_actividad`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'log_actividad es append-only: DELETE prohibido.';
END$$
DELIMITER ;

-- =============================================================================
-- FIN DEL BORRADOR — pendiente de validación del Arquitecto antes de:
--   (a) registrarse en knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md como tabla
--       "Materializado en SQL", y
--   (b) ejecutarse (vía api/setup_diagnostico.php?action=migrate, mismo patrón
--       idempotente que 001/002, sobre el túnel SSH local — nunca directo a
--       staging sin pasar antes por el entorno local del Arquitecto).
-- =============================================================================
