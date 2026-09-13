-- SOLO BASE LOCAL NUEVA. Ejecutar mediante scripts/setup_local_database.php.
-- Ejemplos de presentación, no ofertas reales ni precios aprobados de venta.
USE pinturapittsburgh_local;
START TRANSACTION;
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden) VALUES
('Tu próximo proyecto empieza aquí', 'CONTENIDO DE PRUEBA LOCAL', 'Explora las líneas de pintura y encuentra inspiración para tu espacio.', 'assets/img/personas_pintando_webp.webp', 'Explorar productos', '#productos', 1),
('Planea antes de pintar', 'CONTENIDO DE PRUEBA LOCAL', 'Estima la cantidad de pintura con la calculadora de referencia.', 'assets/img/brocha_webp.webp', 'Abrir calculadora', '#calculadora', 2),
('Estamos cerca de tu proyecto', 'CONTENIDO DE PRUEBA LOCAL', 'Consulta la cobertura de entrega de nuestra tienda en La Paz, B.C.S.', 'assets/img/maya_manos_webp.webp', 'Consultar cobertura', '#postal', 3);
INSERT INTO cupones (codigo, badge, descripcion, imagen_url, url, tipo_descuento, valor_descuento, fecha_inicio, fecha_fin) VALUES
('LOCAL10', '10% · PRUEBA', 'Promoción de prueba local. No canjeable; solo para revisar el diseño.', 'assets/img/brocha_webp.webp', '#contacto', 'porcentaje', 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
('LOCAL50', '$50 · PRUEBA', 'Cupón de ejemplo local. No constituye una oferta comercial.', 'assets/img/cepillo_pintura_webp.webp', '#contacto', 'monto_fijo', 50, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY));
INSERT INTO productos (nombre, linea, sustrato, grado_brillo, propiedades_funcionales, descripcion, precio_lista, can_cut_url) VALUES
('Speedhide — muestra local', 'speedhide', 'concreto_costero', 'mate', '[]', 'Producto de prueba. Precio e inventario ficticios.', 500, 'assets/img/familia_productos_webp.webp'),
('Manor Hall — muestra local', 'manor_hall', 'enjarre_yeso', 'satinado', '[]', 'Producto de prueba. Precio e inventario ficticios.', 600, 'assets/img/familia_productos_webp.webp'),
('Perma-Crete — muestra local', 'perma_crete', 'concreto_costero', 'mate', '[]', 'Producto de prueba. Precio e inventario ficticios.', 700, 'assets/img/familia_productos_webp.webp'),
('Pitt-Glaze — muestra local', 'pitt_glaze', 'herreria', 'semibrillante', '[]', 'Producto de prueba. Precio e inventario ficticios.', 800, 'assets/img/familia_productos_webp.webp');
INSERT INTO producto_presentaciones (producto_id, volumen, sku, precio, stock)
SELECT id, 'galon', CONCAT('LOCAL-G-', id), precio_lista, 20 FROM productos;
-- Registro técnico ficticio y expirado: satisface la FK y el JOIN del feed.
-- No contiene credenciales Meta ni permite publicar en redes sociales.
INSERT INTO social_tokens (plataforma, cuenta_id_externa, nonce, dek_envuelta, auth_tag, token_cifrado, expira_en)
VALUES ('facebook', 'LOCAL_NO_ES_CUENTA_REAL', REPEAT(CHAR(0),12), 'LOCAL_INVALIDO', REPEAT(CHAR(0),16), 'LOCAL_INVALIDO', '2000-01-01');
SET @local_social_id = LAST_INSERT_ID();
INSERT INTO publicaciones_sociales (social_token_id, texto, media_url, estado, publicado_en) VALUES
(@local_social_id, '[PRUEBA LOCAL] Ideas para renovar tu espacio. Publicación de ejemplo almacenada en MariaDB.', 'assets/img/brocha_webp.webp', 'publicada', NOW()),
(@local_social_id, '[PRUEBA LOCAL] Prepara tus superficies antes de pintar. Contenido de ejemplo para el feed.', 'assets/img/maya_manos_webp.webp', 'publicada', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@local_social_id, '[PRUEBA LOCAL] Encuentra inspiración para tu siguiente proyecto en La Paz.', 'assets/img/color_anio_webp.webp', 'publicada', DATE_SUB(NOW(), INTERVAL 2 DAY));
COMMIT;
