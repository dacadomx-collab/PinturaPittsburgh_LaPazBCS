-- =============================================================================
-- 004_ubicacion_banners.sql — PinturaPittsburgh_LaPazBCS (Hito 27)
-- Adopta el hallazgo de Rafael (collab/rafa, database/007_ubicacion_banners.sql)
-- al promover su frontend a `main`: agrega un campo aditivo a `banners` para
-- ubicar cada campaña en una sección concreta del sitio (inicio/nosotros/
-- inspiracion) — su versión ya lo consume en `api/banners_listar.php`.
-- Aditivo y repetible: no toca filas existentes, no elimina nada.
-- =============================================================================
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'banners' AND COLUMN_NAME = 'ubicacion');
SET @ddl = IF(@col_exists = 0, 'ALTER TABLE banners ADD COLUMN ubicacion VARCHAR(40) NOT NULL DEFAULT ''inicio''', 'SELECT 1');
PREPARE banner_stmt FROM @ddl;
EXECUTE banner_stmt;
DEALLOCATE PREPARE banner_stmt;
