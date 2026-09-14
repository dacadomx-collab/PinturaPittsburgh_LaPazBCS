-- Metadato aditivo para ubicar banners editoriales. MySQL/MariaDB.
-- Repetible: conserva los registros y la ubicación por defecto de campañas.
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'banners' AND COLUMN_NAME = 'ubicacion');
SET @ddl = IF(@col_exists = 0, 'ALTER TABLE banners ADD COLUMN ubicacion VARCHAR(40) NOT NULL DEFAULT ''inicio''', 'SELECT 1');
PREPARE banner_stmt FROM @ddl;
EXECUTE banner_stmt;
DEALLOCATE PREPARE banner_stmt;
