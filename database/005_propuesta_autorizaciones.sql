-- =============================================================================
-- Migración 005 — Registro de autorización/firma de la Cotización ACADEP
-- =============================================================================
-- Propósito: Propuesta/index.html (cotización COT-ACADEP-2026-001, ACADEP -> Famza)
-- no guardaba ningún registro de que el cliente hubiera aceptado y firmado — solo
-- dibujaba la firma en un <canvas> y el botón de imprimir generaba un PDF local,
-- sin que quedara ninguna constancia del lado del servidor. Esta tabla es
-- exclusivamente el registro de aceptación de ESA cotización (un documento
-- comercial de ACADEP hacia Famza) — no es un dato del negocio de Famza en sí,
-- por eso lleva el prefijo "acadep_" y vive separada visualmente de las tablas
-- propias del negocio (users, productos, banners, cupones, etc.), aunque para
-- simplicidad de infraestructura comparte la misma base de datos ya operativa.
--
-- Autorizado explícitamente por el Arquitecto (2026-09-18) tras detectar que
-- el formulario de autorización no dejaba ningún rastro server-side.
-- Idempotente: no falla si ya existe.
-- =============================================================================

CREATE TABLE IF NOT EXISTS acadep_cotizacion_autorizaciones (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cotizacion_numero    VARCHAR(40)  NOT NULL DEFAULT 'COT-ACADEP-2026-001',
    autoriza_desarrollo  TINYINT(1)   NOT NULL DEFAULT 0,
    autoriza_iguala      TINYINT(1)   NOT NULL DEFAULT 0,
    nombre_autoriza      VARCHAR(160) NOT NULL,
    fecha_autorizacion   DATE         NOT NULL,
    firma_png            LONGTEXT     NOT NULL,
    ip_hash              CHAR(64)     NULL,
    user_agent_hash      CHAR(64)     NULL,
    creado_en            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_acadep_cotizacion_numero (cotizacion_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
