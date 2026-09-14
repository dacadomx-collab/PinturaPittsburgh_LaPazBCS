-- Exclusivamente instancia local. Repetible sin duplicar campañas de prueba.
USE pinturapittsburgh_local;
START TRANSACTION;
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden)
SELECT 'Color Collection 2026', 'INSPIRACIÓN FAMZA · PRUEBA LOCAL', 'Explora combinaciones de color y encuentra tu próxima paleta con Famza.', 'assets/img/color_collection.webp', 'Visita Famza', 'tienda.html', 4
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE imagen_url = 'assets/img/color_collection.webp');
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden)
SELECT 'Salvia: inspiración natural', 'INSPIRACIÓN FAMZA · PRUEBA LOCAL', 'Descubre la inspiración del tono Salvia PPG1124-6 para tu espacio.', 'assets/img/salvia.webp', 'Recibe asesoría', 'tienda.html', 5
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE imagen_url = 'assets/img/salvia.webp');
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden)
SELECT 'Conoce Pitt-Tech Plus EP', 'INSPIRACIÓN FAMZA · PRUEBA LOCAL', 'Consulta con Famza las opciones de acabado para tu proyecto y su disponibilidad.', 'assets/img/image_3.webp', 'Consultar productos', 'productos.html', 6
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE imagen_url = 'assets/img/image_3.webp');
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden)
SELECT 'Speed Cryl para tu exterior', 'INSPIRACIÓN FAMZA · PRUEBA LOCAL', 'Planea la renovación de tus exteriores con asesoría de Famza. Consulta disponibilidad en tienda.', 'assets/img/SpeedCryl.webp', 'Encuentra tu tienda', 'tienda.html', 7
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE imagen_url = 'assets/img/SpeedCryl.webp');
COMMIT;
