-- =============================================================================
-- database/002_banners_cupones_colaborador.sql — PinturaPittsburgh_LaPazBCS
-- Migración 002 — Autorizada por el Arquitecto (2026-09-12): rol 'colaborador',
-- tabla `banners`, tabla `cupones`.
--
-- REGLA DE ORO (Directiva 1): solo estructura (DDL). La alta del usuario
-- Rafael NO vive en este script — se aprovisiona con `scripts/seed_admin.php`
-- (ya diseñado para generar contraseñas seguras y nunca tocar Git), evitando
-- depositar una credencial de una persona real en un archivo versionado.
--
-- Aplicar DESPUÉS de database/001_schema_inicial.sql.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1a. users.role — agrega 'colaborador' al ENUM existente (admin/staff).
--     Mismo NOT NULL / DEFAULT que la definición original (001).
-- -----------------------------------------------------------------------------
ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('admin','staff','colaborador') NOT NULL DEFAULT 'staff';

-- -----------------------------------------------------------------------------
-- 1b. banners — Banners promocionales de cabecera (Contrato 11).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `banners` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo`      VARCHAR(150)    NOT NULL,
    `subtitulo`   VARCHAR(255)    NULL,
    `imagen_url`  VARCHAR(500)    NOT NULL,
    `cta_texto`   VARCHAR(60)     NULL COMMENT 'Texto del botón de llamada a la acción',
    `cta_url`     VARCHAR(255)    NULL COMMENT 'Destino del botón — puede ser un producto, categoría o promoción',
    `orden`       INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Orden de aparición en el carrusel',
    `activo`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_banners_activo_orden` (`activo`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 1c. cupones — Promociones y cupones temporales (Contrato 12).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cupones` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`          VARCHAR(30)     NULL COMMENT 'NULL si es promo informativa sin código canjeable',
    `descripcion`     VARCHAR(255)    NOT NULL,
    `tipo_descuento`  ENUM('porcentaje','monto_fijo') NOT NULL,
    `valor_descuento` DECIMAL(10,2)   NOT NULL COMMENT 'Nunca debe resultar en precio final por debajo de productos.precio_minimo_map (regla MAP)',
    `fecha_inicio`    DATE            NOT NULL,
    `fecha_fin`       DATE            NOT NULL,
    `activo`          TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cupones_codigo` (`codigo`),
    KEY `idx_cupones_vigencia` (`activo`, `fecha_inicio`, `fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- NOTA: para dar de alta a Rafael (role='colaborador') una vez aplicada esta
-- migración, usar scripts/seed_admin.php (extendido para aceptar rol):
--
--   php scripts/seed_admin.php armandocastillejos086@gmail.com "<password>" colaborador
--
-- Nunca se escribe aquí un INSERT con su email/hash — ver knowledge/02_CODEX_
-- Y_SCHEMA_MAESTRO.md y el reporte de ejecución para la contraseña generada.
-- =============================================================================
