-- =============================================================================
-- database/001_schema_inicial.sql — PinturaPittsburgh_LaPazBCS
-- Schema Maestro v2.0 — MATERIALIZADO desde knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md
-- Aprobado por el Arquitecto: 2026-09-11 (Directiva 1 — Hito 2)
--
-- Motor: MariaDB / MySQL 8+ | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Motor de almacenamiento: InnoDB (soporta FKs y transacciones)
--
-- REGLA DE ORO (Directiva 1): todo helper/endpoint PHP posterior DEBE usar
-- exactamente estos nombres de columna como nombres de variable/clave de
-- array para erradicar errores 1054 (Unknown column). No renombrar aquí sin
-- actualizar knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md en el mismo hito
-- (Mandamiento 9 — Inmutabilidad del Sistema).
--
-- Orden de creación respeta las dependencias de llaves foráneas.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. users — Cuentas de administración/staff del backoffice (Bearer JWT).
--    Columnas consumidas ya por api/auth_login.php y api/auth_refresh.php.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`          VARCHAR(190)    NOT NULL,
    `password_hash`  VARCHAR(255)    NOT NULL COMMENT 'password_hash(PASSWORD_BCRYPT, [cost=>12])',
    `role`           ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    `estatus`        ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. productos — Catálogo maestro (independiente de presentación/volumen).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`                   VARCHAR(150)    NOT NULL,
    `linea`                    ENUM('speedhide','manor_hall','perma_crete','pitt_glaze','otra') NOT NULL,
    `sustrato`                 ENUM('concreto_costero','enjarre_yeso','tabla_roca','madera_marina','herreria','piso_alto_transito') NULL,
    `grado_brillo`             ENUM('mate','eggshell','satinado','semibrillante','brillante') NULL,
    `propiedades_funcionales`  JSON            NULL COMMENT 'Array de tags: ["anti_salitre","zero_voc","one_coat_hide","cool_surface"]',
    `descripcion`              TEXT            NULL,
    `precio_lista`             DECIMAL(10,2)   NOT NULL,
    `precio_minimo_map`        DECIMAL(10,2)   NULL COMMENT 'Piso MAP del fabricante — nunca publicitar por debajo',
    `can_cut_url`              VARCHAR(500)    NULL COMMENT 'Render oficial del Marketing Hub',
    `ficha_tecnica_pdf_url`    VARCHAR(500)    NULL,
    `ficha_seguridad_pdf_url`  VARCHAR(500)    NULL,
    `activo`                   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_productos_linea` (`linea`),
    KEY `idx_productos_sustrato` (`sustrato`),
    KEY `idx_productos_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. producto_presentaciones — SKU y precio por volumen.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto_presentaciones` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `producto_id`    BIGINT UNSIGNED NOT NULL,
    `volumen`        ENUM('cuarto_galon','galon','cubeta') NOT NULL,
    `sku`            VARCHAR(40)     NOT NULL,
    `precio`         DECIMAL(10,2)   NOT NULL,
    `stock`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_presentaciones_sku` (`sku`),
    KEY `idx_presentaciones_producto` (`producto_id`),
    CONSTRAINT `fk_presentaciones_producto`
        FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. codigos_postales_cobertura — Lista blanca de entrega a domicilio (La Paz).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `codigos_postales_cobertura` (
    `codigo_postal`        CHAR(5)     NOT NULL,
    `zona_colonia`         VARCHAR(150) NOT NULL,
    `modalidad_logistica`  ENUM('despacho_local','ruta_programada','despacho_inmediato','ruta_periferica') NOT NULL,
    `ventana_entrega`      VARCHAR(60)  NOT NULL,
    `activo`               TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`codigo_postal`),
    KEY `idx_cobertura_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. pedidos — Órdenes de compra (checkout con validación postal).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cliente_nombre`      VARCHAR(150)    NOT NULL,
    `cliente_telefono`    VARCHAR(20)     NOT NULL,
    `cliente_email`       VARCHAR(190)    NULL,
    `cp_entrega`          CHAR(5)         NULL COMMENT 'NULL si modalidad=recoleccion_tienda',
    `direccion_entrega`   VARCHAR(255)    NULL,
    `modalidad`           ENUM('entrega_domicilio','recoleccion_tienda') NOT NULL,
    `estatus`             ENUM('pendiente','confirmado','en_ruta','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    `total`               DECIMAL(10,2)   NOT NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pedidos_estatus` (`estatus`),
    KEY `idx_pedidos_cp` (`cp_entrega`),
    CONSTRAINT `fk_pedidos_cp_cobertura`
        FOREIGN KEY (`cp_entrega`) REFERENCES `codigos_postales_cobertura` (`codigo_postal`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. pedido_items — Renglones de cada pedido (snapshot de precio).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedido_items` (
    `id`                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pedido_id`                   BIGINT UNSIGNED NOT NULL,
    `producto_presentacion_id`    BIGINT UNSIGNED NOT NULL,
    `cantidad`                    INT UNSIGNED    NOT NULL,
    `precio_unitario`             DECIMAL(10,2)   NOT NULL COMMENT 'Snapshot al momento del pedido — nunca referenciar precio actual a futuro',
    PRIMARY KEY (`id`),
    KEY `idx_items_pedido` (`pedido_id`),
    KEY `idx_items_presentacion` (`producto_presentacion_id`),
    CONSTRAINT `fk_items_pedido`
        FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_presentacion`
        FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentaciones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. social_tokens — Tokens Meta Graph API cifrados (Envelope Encryption
--    AES-256-GCM). Ver helpers/crypto_helper.php para el flujo de
--    cifrado/descifrado. La KEK maestra vive SOLO en .env (META_TOKEN_KEK),
--    nunca en esta tabla.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `social_tokens` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plataforma`          ENUM('facebook','instagram') NOT NULL,
    `cuenta_id_externa`   VARCHAR(60)     NOT NULL COMMENT 'page-id o ig-user-id de Meta',
    `nonce`               BINARY(12)      NOT NULL COMMENT 'IV de 96 bits — único por cifrado, nunca reutilizado',
    `dek_envuelta`        VARBINARY(255)  NOT NULL COMMENT 'DEK cifrada con la KEK maestra (wrapping)',
    `auth_tag`            BINARY(16)      NOT NULL COMMENT 'Tag de autenticación GCM',
    `token_cifrado`       VARBINARY(1024) NOT NULL,
    `expira_en`           DATETIME        NULL COMMENT '~60 días — renovación proactiva vía fb_exchange_token',
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_social_tokens_plataforma` (`plataforma`),
    KEY `idx_social_tokens_expira` (`expira_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. publicaciones_sociales — Cola del Publicador Social (Facebook/Instagram).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `publicaciones_sociales` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `social_token_id`      BIGINT UNSIGNED NOT NULL,
    `producto_id`          BIGINT UNSIGNED NULL,
    `texto`                TEXT            NOT NULL,
    `media_url`            VARCHAR(500)    NOT NULL,
    `estado`               ENUM('borrador','programada','publicada','fallida') NOT NULL DEFAULT 'borrador',
    `programado_para`      DATETIME        NULL,
    `media_container_id`   VARCHAR(60)     NULL COMMENT 'Solo Instagram — id temporal del contenedor (fase 1 async)',
    `post_id_externo`      VARCHAR(60)     NULL COMMENT 'id permanente devuelto por Meta al publicar',
    `publicado_en`         DATETIME        NULL,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_publicaciones_estado` (`estado`),
    KEY `idx_publicaciones_producto` (`producto_id`),
    CONSTRAINT `fk_publicaciones_social_token`
        FOREIGN KEY (`social_token_id`) REFERENCES `social_tokens` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_publicaciones_producto`
        FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED — codigos_postales_cobertura (23000 a 23098, zona urbana de La Paz)
--
-- Fuente de zonas nombradas: knowledge/Estrategia Omnicanal...txt §4 (tabla de
-- rangos de CP con SLA). Los CP dentro de un rango documentado heredan su
-- zona/modalidad/ventana. Los CP del rango 23000-23098 NO cubiertos
-- explícitamente por la fuente se insertan como
-- "Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)" con
-- modalidad 'ruta_programada' / 'Mismo Dia' como valor por defecto razonable
-- (Mandamiento 4 — no se inventa un nombre de colonia específico sin fuente).
--
-- ⚠️ ACCIÓN PENDIENTE: sustituir las filas "pendiente de verificar" con el
-- catálogo oficial SEPOMEX antes de producción.
-- =============================================================================
INSERT INTO `codigos_postales_cobertura`
    (`codigo_postal`, `zona_colonia`, `modalidad_logistica`, `ventana_entrega`, `activo`)
VALUES
    ('23000', 'Zona Central, Love, Puerta de Hierro', 'despacho_local', 'Menos de 2 a 4 Horas', 1),
    ('23001', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23002', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23003', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23004', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23005', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23006', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23007', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23008', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23009', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23010', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23011', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23012', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23013', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23014', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23015', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23016', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23017', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23018', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23019', 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', 'ruta_programada', 'Mismo Dia', 1),
    ('23020', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23021', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23022', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23023', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23024', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23025', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23026', 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', 'ruta_programada', 'Mismo Dia', 1),
    ('23027', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23028', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23029', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23030', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23031', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23032', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23033', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23034', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23035', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23036', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23037', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23038', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23039', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23040', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23041', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23042', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23043', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23044', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23045', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23046', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23047', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23048', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23049', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23050', 'Los Olivos, Bella Vista, Roma, Tecnologico, Indeco', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23051', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23052', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23053', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23054', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23055', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23056', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23057', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23058', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23059', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23060', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23061', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23062', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23063', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23064', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23065', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23066', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23067', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23068', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23069', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23070', 'Balandra, Las Garzas, Privadas, Agustin Arriola', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23071', 'Balandra, Las Garzas, Privadas, Agustin Arriola', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23072', 'Balandra, Las Garzas, Privadas, Agustin Arriola', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23073', 'Balandra, Las Garzas, Privadas, Agustin Arriola', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23074', 'Balandra, Las Garzas, Privadas, Agustin Arriola', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23075', 'Balandra, Las Garzas, Privadas, Agustin Arriola', 'despacho_inmediato', 'Menos de 2 Horas', 1),
    ('23076', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23077', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23078', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23079', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23080', '8 de Octubre, Altamira Residencial, Camino Real', 'ruta_programada', 'Mismo Dia', 1),
    ('23081', '8 de Octubre, Altamira Residencial, Camino Real', 'ruta_programada', 'Mismo Dia', 1),
    ('23082', '8 de Octubre, Altamira Residencial, Camino Real', 'ruta_programada', 'Mismo Dia', 1),
    ('23083', '8 de Octubre, Altamira Residencial, Camino Real', 'ruta_programada', 'Mismo Dia', 1),
    ('23084', '8 de Octubre, Altamira Residencial, Camino Real', 'ruta_programada', 'Mismo Dia', 1),
    ('23085', '8 de Octubre, Altamira Residencial, Camino Real', 'ruta_programada', 'Mismo Dia', 1),
    ('23086', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23087', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23088', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23089', 'Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)', 'ruta_programada', 'Mismo Dia', 1),
    ('23090', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23091', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23092', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23093', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23094', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23095', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23096', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23097', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1),
    ('23098', 'Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz', 'ruta_periferica', 'Mismo Dia / Hasta 24 Horas', 1);

-- =============================================================================
-- NOTA: no se precarga ningún usuario admin en este script. El primer usuario
-- se crea con scripts/generate_env.php o un script dedicado que invoque
-- password_hash() en tiempo de ejecución — nunca con un hash escrito a mano
-- en un script SQL versionado (Mandamiento 4 — no se fabrican datos que deben
-- ser criptográficamente válidos).
-- =============================================================================
