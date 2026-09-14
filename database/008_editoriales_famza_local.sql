-- Solo instancia local; ejecutar 007 antes. Sin duplicados por ubicación/imagen.
USE pinturapittsburgh_local;
START TRANSACTION;
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden, ubicacion)
SELECT 'Conoce a Pita, tu Pitahayita de confianza.', 'EL LADO MÁS COLORIDO DE FAMZA', 'Nuestra mascota pone una sonrisa y mucho color a la familia Famza. Una invitación a imaginar nuevas combinaciones y darle personalidad a cada espacio.', 'assets/img/pitahaya.webp', 'Encuentra tu inspiración', 'inspiracion.html', 1, 'nosotros'
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE ubicacion = 'nosotros' AND imagen_url = 'assets/img/pitahaya.webp');
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden, ubicacion)
SELECT 'Grandes productos. Nuevas posibilidades.', 'EL RESPALDO DE SIEMPRE', 'Detrás de cada espacio que se transforma, hay una pintura en la que puedes confiar. Somos Famza the colour boutique by Pittsburgh Paints. Acompañamos los proyectos de tu hogar, negocio y obra con 36 años de experiencia y pinturas Pittsburgh, con atención local para ayudarte a elegir el recubrimiento adecuado desde la primera mano.', 'assets/img/familia_productos_webp.webp', 'Encuentra la pintura para tu proyecto', 'productos.html', 2, 'nosotros'
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE ubicacion = 'nosotros' AND imagen_url = 'assets/img/familia_productos_webp.webp');
INSERT INTO banners (titulo, eyebrow, descripcion, imagen_url, cta_texto, cta_url, orden, ubicacion)
SELECT 'Más que un color. Una sensación.', 'COLOR DEL AÑO 2026 · WARM MAHOGANY PPG1060-7', 'Profundo, cálido y lleno de carácter. Un rojo que invita a bajar el ritmo y crear espacios que se sienten como hogar.', 'assets/img/color_anio_webp.webp', 'Descubre la inspiración', 'https://www.pittsburghpaintsco.com/', 1, 'inspiracion'
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE ubicacion = 'inspiracion' AND imagen_url = 'assets/img/color_anio_webp.webp');
COMMIT;
