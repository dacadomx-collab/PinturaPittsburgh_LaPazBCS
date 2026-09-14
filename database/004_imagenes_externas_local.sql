-- SOLO pruebas locales. Fotografías genéricas externas, no imágenes de producto.
USE pinturapittsburgh_local;
START TRANSACTION;
UPDATE banners SET imagen_url = 'https://picsum.photos/id/1040/1000/500' WHERE id = 1 AND eyebrow = 'CONTENIDO DE PRUEBA LOCAL';
UPDATE banners SET imagen_url = 'https://picsum.photos/id/1060/1000/500' WHERE id = 2 AND eyebrow = 'CONTENIDO DE PRUEBA LOCAL';
UPDATE banners SET imagen_url = 'https://picsum.photos/id/1018/1000/500' WHERE id = 3 AND eyebrow = 'CONTENIDO DE PRUEBA LOCAL';
UPDATE cupones SET imagen_url = 'https://picsum.photos/id/1040/700/500' WHERE codigo = 'LOCAL10';
UPDATE cupones SET imagen_url = 'https://picsum.photos/id/1060/700/500' WHERE codigo = 'LOCAL50';
COMMIT;
