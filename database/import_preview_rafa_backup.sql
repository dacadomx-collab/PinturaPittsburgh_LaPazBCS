-- MariaDB dump 10.20-12.3.3-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: pinturapittsburgh_local
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `pinturapittsburgh_local`
--

-- CREATE DATABASE/USE originales removidos a propósito: este dump se importa
-- directo dentro de tourfindycom_pittsburgh_preview (ya creada en el servidor
-- real), nunca recreando `pinturapittsburgh_local` (nombre de la instancia
-- local de Rafael, sin relación con el hosting compartido).

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `eyebrow` varchar(100) DEFAULT NULL COMMENT 'Texto corto sobre el título (ej. "Nueva línea", "Exclusivo La Paz")',
  `descripcion` text DEFAULT NULL,
  `imagen_url` varchar(500) NOT NULL,
  `cta_texto` varchar(60) DEFAULT NULL COMMENT 'Texto del botón de llamada a la acción',
  `cta_url` varchar(500) DEFAULT NULL COMMENT 'Destino del botón — puede ser un producto, categoría o promoción',
  `orden` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Orden de aparición en el carrusel',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ubicacion` varchar(40) NOT NULL DEFAULT 'inicio',
  PRIMARY KEY (`id`),
  KEY `idx_banners_activo_orden` (`activo`,`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES
(1,'Tu próximo proyecto empieza aquí','CONTENIDO DE PRUEBA LOCAL','Explora las líneas de pintura y encuentra inspiración para tu espacio.','assets/img/personas_pintando_webp.webp','Explorar productos','#productos',1,1,'2026-09-12 18:57:14','2026-09-14 09:40:07','inicio'),
(2,'Planea antes de pintar','CONTENIDO DE PRUEBA LOCAL','Estima la cantidad de pintura con la calculadora de referencia.','assets/img/brocha_webp.webp','Abrir calculadora','#calculadora',2,1,'2026-09-12 18:57:14','2026-09-14 09:40:07','inicio'),
(3,'Estamos cerca de tu proyecto','CONTENIDO DE PRUEBA LOCAL','Consulta la cobertura de entrega de nuestra tienda en La Paz, B.C.S.','assets/img/maya_manos_webp.webp','Consultar cobertura','#postal',3,1,'2026-09-12 18:57:14','2026-09-14 09:40:07','inicio'),
(4,'Color Collection 2026','INSPIRACIÓN FAMZA · PRUEBA LOCAL','Explora combinaciones de color y encuentra tu próxima paleta con Famza.','assets/img/color_collection.webp','Visita Famza','tienda.html',4,1,'2026-09-14 16:19:26','2026-09-14 16:19:26','inicio'),
(5,'Salvia: inspiración natural','INSPIRACIÓN FAMZA · PRUEBA LOCAL','Descubre la inspiración del tono Salvia PPG1124-6 para tu espacio.','assets/img/salvia.webp','Recibe asesoría','tienda.html',5,1,'2026-09-14 16:19:26','2026-09-14 16:19:26','inicio'),
(6,'Conoce Pitt-Tech Plus EP','INSPIRACIÓN FAMZA · PRUEBA LOCAL','Consulta con Famza las opciones de acabado para tu proyecto y su disponibilidad.','assets/img/image_3.webp','Consultar productos','productos.html',6,1,'2026-09-14 16:19:26','2026-09-14 16:19:26','inicio'),
(7,'Speed Cryl para tu exterior','INSPIRACIÓN FAMZA · PRUEBA LOCAL','Planea la renovación de tus exteriores con asesoría de Famza. Consulta disponibilidad en tienda.','assets/img/SpeedCryl.webp','Encuentra tu tienda','tienda.html',7,1,'2026-09-14 16:19:26','2026-09-14 16:19:26','inicio'),
(8,'Conoce a Pita, tu Pitahayita de confianza.','EL LADO MÁS COLORIDO DE FAMZA','Nuestra mascota pone una sonrisa y mucho color a la familia Famza. Una invitación a imaginar nuevas combinaciones y darle personalidad a cada espacio.','assets/img/pitahaya.webp','Encuentra tu inspiración','inspiracion.html',1,1,'2026-09-14 16:33:31','2026-09-14 16:33:31','nosotros'),
(9,'Grandes productos. Nuevas posibilidades.','EL RESPALDO DE SIEMPRE','Detrás de cada espacio que se transforma, hay una pintura en la que puedes confiar. Somos Famza the colour boutique by Pittsburgh Paints. Acompañamos los proyectos de tu hogar, negocio y obra con 36 años de experiencia y pinturas Pittsburgh, con atención local para ayudarte a elegir el recubrimiento adecuado desde la primera mano.','assets/img/familia_productos_webp.webp','Encuentra la pintura para tu proyecto','productos.html',2,1,'2026-09-14 16:33:31','2026-09-14 16:33:31','nosotros'),
(10,'Más que un color. Una sensación.','COLOR DEL AÑO 2026 · WARM MAHOGANY PPG1060-7','Profundo, cálido y lleno de carácter. Un rojo que invita a bajar el ritmo y crear espacios que se sienten como hogar.','assets/img/color_anio_webp.webp','Descubre la inspiración','https://www.pittsburghpaintsco.com/',1,1,'2026-09-14 16:33:31','2026-09-14 16:33:31','inspiracion');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `codigos_postales_cobertura`
--

DROP TABLE IF EXISTS `codigos_postales_cobertura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `codigos_postales_cobertura` (
  `codigo_postal` char(5) NOT NULL,
  `zona_colonia` varchar(150) NOT NULL,
  `modalidad_logistica` enum('despacho_local','ruta_programada','despacho_inmediato','ruta_periferica') NOT NULL,
  `ventana_entrega` varchar(60) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`codigo_postal`),
  KEY `idx_cobertura_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codigos_postales_cobertura`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `codigos_postales_cobertura` WRITE;
/*!40000 ALTER TABLE `codigos_postales_cobertura` DISABLE KEYS */;
INSERT INTO `codigos_postales_cobertura` VALUES
('23000','Zona Central, Love, Puerta de Hierro','despacho_local','Menos de 2 a 4 Horas',1),
('23001','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23002','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23003','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23004','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23005','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23006','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23007','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23008','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23009','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23010','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23011','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23012','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23013','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23014','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23015','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23016','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23017','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23018','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23019','Colina del Sol, Ciudad del Cielo, Palmira, Pedregal','ruta_programada','Mismo Dia',1),
('23020','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23021','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23022','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23023','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23024','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23025','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23026','El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio','ruta_programada','Mismo Dia',1),
('23027','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23028','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23029','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23030','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23031','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23032','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23033','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23034','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23035','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23036','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23037','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23038','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23039','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23040','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23041','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23042','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23043','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23044','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23045','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23046','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23047','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23048','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23049','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23050','Los Olivos, Bella Vista, Roma, Tecnologico, Indeco','despacho_inmediato','Menos de 2 Horas',1),
('23051','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23052','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23053','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23054','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23055','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23056','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23057','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23058','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23059','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23060','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23061','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23062','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23063','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23064','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23065','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23066','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23067','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23068','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23069','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23070','Balandra, Las Garzas, Privadas, Agustin Arriola','despacho_inmediato','Menos de 2 Horas',1),
('23071','Balandra, Las Garzas, Privadas, Agustin Arriola','despacho_inmediato','Menos de 2 Horas',1),
('23072','Balandra, Las Garzas, Privadas, Agustin Arriola','despacho_inmediato','Menos de 2 Horas',1),
('23073','Balandra, Las Garzas, Privadas, Agustin Arriola','despacho_inmediato','Menos de 2 Horas',1),
('23074','Balandra, Las Garzas, Privadas, Agustin Arriola','despacho_inmediato','Menos de 2 Horas',1),
('23075','Balandra, Las Garzas, Privadas, Agustin Arriola','despacho_inmediato','Menos de 2 Horas',1),
('23076','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23077','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23078','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23079','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23080','8 de Octubre, Altamira Residencial, Camino Real','ruta_programada','Mismo Dia',1),
('23081','8 de Octubre, Altamira Residencial, Camino Real','ruta_programada','Mismo Dia',1),
('23082','8 de Octubre, Altamira Residencial, Camino Real','ruta_programada','Mismo Dia',1),
('23083','8 de Octubre, Altamira Residencial, Camino Real','ruta_programada','Mismo Dia',1),
('23084','8 de Octubre, Altamira Residencial, Camino Real','ruta_programada','Mismo Dia',1),
('23085','8 de Octubre, Altamira Residencial, Camino Real','ruta_programada','Mismo Dia',1),
('23086','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23087','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23088','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23089','Zona Urbana La Paz - colonia pendiente de verificar (SEPOMEX)','ruta_programada','Mismo Dia',1),
('23090','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23091','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23092','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23093','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23094','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23095','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23096','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23097','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1),
('23098','Miramar, Arcos del Sol, Atardeceres, Bahia de la Paz','ruta_periferica','Mismo Dia / Hasta 24 Horas',1);
/*!40000 ALTER TABLE `codigos_postales_cobertura` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cupones`
--

DROP TABLE IF EXISTS `cupones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cupones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL COMMENT 'Toda promoción listada requiere código canjeable — ya no admite NULL',
  `badge` varchar(60) DEFAULT NULL COMMENT 'Etiqueta corta de la tarjeta (ej. "-20%", "2x1")',
  `descripcion` text DEFAULT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL COMMENT 'Destino de la tarjeta de promoción (producto, categoría, landing)',
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL COMMENT 'Nunca debe resultar en precio final por debajo de productos.precio_minimo_map (regla MAP)',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cupones_codigo` (`codigo`),
  KEY `idx_cupones_vigencia` (`activo`,`fecha_inicio`,`fecha_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cupones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cupones` WRITE;
/*!40000 ALTER TABLE `cupones` DISABLE KEYS */;
INSERT INTO `cupones` VALUES
(1,'LOCAL10','10% · PRUEBA','Promoción de prueba local. No canjeable; solo para revisar el diseño.','assets/img/brocha_webp.webp','#contacto','porcentaje',10.00,'2026-09-12','2026-10-12',1,'2026-09-12 18:57:14','2026-09-14 09:40:07'),
(2,'LOCAL50','$50 · PRUEBA','Cupón de ejemplo local. No constituye una oferta comercial.','assets/img/cepillo_pintura_webp.webp','#contacto','monto_fijo',50.00,'2026-09-12','2026-10-12',1,'2026-09-12 18:57:14','2026-09-14 09:40:07');
/*!40000 ALTER TABLE `cupones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pedido_items`
--

DROP TABLE IF EXISTS `pedido_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint(20) unsigned NOT NULL,
  `producto_presentacion_id` bigint(20) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Snapshot al momento del pedido — nunca referenciar precio actual a futuro',
  PRIMARY KEY (`id`),
  KEY `idx_items_pedido` (`pedido_id`),
  KEY `idx_items_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_items_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pedido_items` WRITE;
/*!40000 ALTER TABLE `pedido_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedido_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_nombre` varchar(150) NOT NULL,
  `cliente_telefono` varchar(20) NOT NULL,
  `cliente_email` varchar(190) DEFAULT NULL,
  `cp_entrega` char(5) DEFAULT NULL COMMENT 'NULL si modalidad=recoleccion_tienda',
  `direccion_entrega` varchar(255) DEFAULT NULL,
  `modalidad` enum('entrega_domicilio','recoleccion_tienda') NOT NULL,
  `estatus` enum('pendiente','confirmado','en_ruta','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `total` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedidos_estatus` (`estatus`),
  KEY `idx_pedidos_cp` (`cp_entrega`),
  CONSTRAINT `fk_pedidos_cp_cobertura` FOREIGN KEY (`cp_entrega`) REFERENCES `codigos_postales_cobertura` (`codigo_postal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `producto_presentaciones`
--

DROP TABLE IF EXISTS `producto_presentaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_presentaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint(20) unsigned NOT NULL,
  `volumen` enum('cuarto_galon','galon','cubeta') NOT NULL,
  `sku` varchar(40) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_presentaciones_sku` (`sku`),
  KEY `idx_presentaciones_producto` (`producto_id`),
  CONSTRAINT `fk_presentaciones_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_presentaciones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `producto_presentaciones` WRITE;
/*!40000 ALTER TABLE `producto_presentaciones` DISABLE KEYS */;
INSERT INTO `producto_presentaciones` VALUES
(1,1,'galon','LOCAL-G-1',500.00,20,'2026-09-12 18:57:14','2026-09-12 18:57:14'),
(2,2,'galon','LOCAL-G-2',600.00,20,'2026-09-12 18:57:14','2026-09-12 18:57:14'),
(3,3,'galon','LOCAL-G-3',700.00,20,'2026-09-12 18:57:14','2026-09-12 18:57:14'),
(4,4,'galon','LOCAL-G-4',800.00,20,'2026-09-12 18:57:14','2026-09-12 18:57:14');
/*!40000 ALTER TABLE `producto_presentaciones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `linea` enum('speedhide','manor_hall','perma_crete','pitt_glaze','otra') NOT NULL,
  `sustrato` enum('concreto_costero','enjarre_yeso','tabla_roca','madera_marina','herreria','piso_alto_transito') DEFAULT NULL,
  `grado_brillo` enum('mate','eggshell','satinado','semibrillante','brillante') DEFAULT NULL,
  `propiedades_funcionales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array de tags: ["anti_salitre","zero_voc","one_coat_hide","cool_surface"]' CHECK (json_valid(`propiedades_funcionales`)),
  `descripcion` text DEFAULT NULL,
  `precio_lista` decimal(10,2) NOT NULL,
  `precio_minimo_map` decimal(10,2) DEFAULT NULL COMMENT 'Piso MAP del fabricante — nunca publicitar por debajo',
  `can_cut_url` varchar(500) DEFAULT NULL COMMENT 'Render oficial del Marketing Hub',
  `ficha_tecnica_pdf_url` varchar(500) DEFAULT NULL,
  `ficha_seguridad_pdf_url` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_productos_linea` (`linea`),
  KEY `idx_productos_sustrato` (`sustrato`),
  KEY `idx_productos_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES
(1,'Speedhide — muestra local','speedhide','concreto_costero','mate','[]','Producto de prueba. Precio e inventario ficticios.',500.00,NULL,'assets/img/familia_productos_webp.webp',NULL,NULL,1,'2026-09-12 18:57:14','2026-09-12 18:57:14'),
(2,'Manor Hall — muestra local','manor_hall','enjarre_yeso','satinado','[]','Producto de prueba. Precio e inventario ficticios.',600.00,NULL,'assets/img/familia_productos_webp.webp',NULL,NULL,1,'2026-09-12 18:57:14','2026-09-12 18:57:14'),
(3,'Perma-Crete — muestra local','perma_crete','concreto_costero','mate','[]','Producto de prueba. Precio e inventario ficticios.',700.00,NULL,'assets/img/familia_productos_webp.webp',NULL,NULL,1,'2026-09-12 18:57:14','2026-09-12 18:57:14'),
(4,'Pitt-Glaze — muestra local','pitt_glaze','herreria','semibrillante','[]','Producto de prueba. Precio e inventario ficticios.',800.00,NULL,'assets/img/familia_productos_webp.webp',NULL,NULL,1,'2026-09-12 18:57:14','2026-09-12 18:57:14');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `publicaciones_sociales`
--

DROP TABLE IF EXISTS `publicaciones_sociales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `publicaciones_sociales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `social_token_id` bigint(20) unsigned NOT NULL,
  `producto_id` bigint(20) unsigned DEFAULT NULL,
  `texto` text NOT NULL,
  `media_url` varchar(500) NOT NULL,
  `estado` enum('borrador','programada','publicada','fallida') NOT NULL DEFAULT 'borrador',
  `programado_para` datetime DEFAULT NULL,
  `media_container_id` varchar(60) DEFAULT NULL COMMENT 'Solo Instagram — id temporal del contenedor (fase 1 async)',
  `post_id_externo` varchar(60) DEFAULT NULL COMMENT 'id permanente devuelto por Meta al publicar',
  `publicado_en` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_publicaciones_estado` (`estado`),
  KEY `idx_publicaciones_producto` (`producto_id`),
  KEY `fk_publicaciones_social_token` (`social_token_id`),
  CONSTRAINT `fk_publicaciones_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_publicaciones_social_token` FOREIGN KEY (`social_token_id`) REFERENCES `social_tokens` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publicaciones_sociales`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `publicaciones_sociales` WRITE;
/*!40000 ALTER TABLE `publicaciones_sociales` DISABLE KEYS */;
INSERT INTO `publicaciones_sociales` VALUES
(1,1,NULL,'[PRUEBA LOCAL] Ideas para renovar tu espacio. Publicación de ejemplo almacenada en MariaDB.','assets/img/brocha_webp.webp','publicada',NULL,NULL,NULL,'2026-09-12 18:57:14','2026-09-12 18:57:14','2026-09-12 18:57:14'),
(2,1,NULL,'[PRUEBA LOCAL] Prepara tus superficies antes de pintar. Contenido de ejemplo para el feed.','assets/img/maya_manos_webp.webp','publicada',NULL,NULL,NULL,'2026-09-11 18:57:14','2026-09-12 18:57:14','2026-09-12 18:57:14'),
(3,1,NULL,'[PRUEBA LOCAL] Encuentra inspiración para tu siguiente proyecto en La Paz.','assets/img/color_anio_webp.webp','publicada',NULL,NULL,NULL,'2026-09-10 18:57:14','2026-09-12 18:57:14','2026-09-12 18:57:14');
/*!40000 ALTER TABLE `publicaciones_sociales` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `social_tokens`
--

DROP TABLE IF EXISTS `social_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plataforma` enum('facebook','instagram') NOT NULL,
  `cuenta_id_externa` varchar(60) NOT NULL COMMENT 'page-id o ig-user-id de Meta',
  `nonce` binary(12) NOT NULL COMMENT 'IV de 96 bits — único por cifrado, nunca reutilizado',
  `dek_envuelta` varbinary(255) NOT NULL COMMENT 'DEK cifrada con la KEK maestra (wrapping)',
  `auth_tag` binary(16) NOT NULL COMMENT 'Tag de autenticación GCM',
  `token_cifrado` varbinary(1024) NOT NULL,
  `expira_en` datetime DEFAULT NULL COMMENT '~60 días — renovación proactiva vía fb_exchange_token',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_social_tokens_plataforma` (`plataforma`),
  KEY `idx_social_tokens_expira` (`expira_en`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `social_tokens` WRITE;
/*!40000 ALTER TABLE `social_tokens` DISABLE KEYS */;
INSERT INTO `social_tokens` VALUES
(1,'facebook','LOCAL_NO_ES_CUENTA_REAL',0x000000000000000000000000,0x4C4F43414C5F494E56414C49444F,0x00000000000000000000000000000000,0x4C4F43414C5F494E56414C49444F,'2000-01-01 00:00:00','2026-09-12 18:57:14','2026-09-12 18:57:14');
/*!40000 ALTER TABLE `social_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'password_hash(PASSWORD_BCRYPT, [cost=>12])',
  `role` enum('admin','staff','colaborador') NOT NULL DEFAULT 'staff',
  `estatus` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping events for database 'pinturapittsburgh_local'
--

--
-- Dumping routines for database 'pinturapittsburgh_local'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-15 11:44:32
