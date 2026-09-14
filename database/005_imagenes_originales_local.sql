-- Prueba local: las rutas originales se obtienen desde BD, no del template HTML.
USE pinturapittsburgh_local;
START TRANSACTION;
UPDATE banners SET imagen_url = 'assets/img/personas_pintando_webp.webp' WHERE id = 1 AND eyebrow = 'CONTENIDO DE PRUEBA LOCAL';
UPDATE banners SET imagen_url = 'assets/img/brocha_webp.webp' WHERE id = 2 AND eyebrow = 'CONTENIDO DE PRUEBA LOCAL';
UPDATE banners SET imagen_url = 'assets/img/maya_manos_webp.webp' WHERE id = 3 AND eyebrow = 'CONTENIDO DE PRUEBA LOCAL';
UPDATE cupones SET imagen_url = 'assets/img/brocha_webp.webp' WHERE codigo = 'LOCAL10';
UPDATE cupones SET imagen_url = 'assets/img/cepillo_pintura_webp.webp' WHERE codigo = 'LOCAL50';
COMMIT;
