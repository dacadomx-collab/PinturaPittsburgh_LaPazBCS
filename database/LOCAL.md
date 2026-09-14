# Base de pruebas local

Excepción autorizada explícitamente por el usuario: base local sin contraseña.
Esta instrucción sustituye la restricción histórica de BD remota únicamente
para esta instalación de pruebas. Staging y producción no se modifican.

## Conexión

- Motor: MariaDB, instancia propia del proyecto.
- Host: `127.0.0.1`.
- Puerto: `3307` (el servicio del sistema en 3306 permanece intacto).
- Base: `pinturapittsburgh_local`.
- Usuario de aplicación: `pp_local`.
- Contraseña: vacía.
- Permisos: SELECT, INSERT, UPDATE, DELETE y tablas temporales, solo en esta BD.
- Persistencia: `logs/local-mariadb/data`, excluida de Git y deploy.

El servidor escucha solo en loopback. No se modifica el usuario root del
MariaDB del sistema. La instancia aislada tiene su propio root local sin
contraseña para administrar exclusivamente sus datos.

## Iniciar después de cerrar la sesión o reiniciar la máquina

Desde la raíz, en una terminal:

```bash
bash scripts/local_database.sh start
```

El proceso queda en primer plano; mantener esa terminal abierta. En otra:

```bash
php -S 127.0.0.1:8000 -t .
```

Abrir **http://127.0.0.1:8000/**, sin `?demo=1`. El modo demo explícito sigue
usando JSON y no sirve para comprobar cambios en MariaDB.

Para detener la base limpiamente, desde otra terminal:

```bash
bash scripts/local_database.sh stop
```

Los datos se conservan entre arranques. No arrancar dos instancias a la vez.

## Instalación inicial en una copia nueva

Requiere PHP con pdo_mysql, mariadbd, mariadb-install-db y mariadb-admin.
Con la instancia iniciada, ejecutar una sola vez:

```bash
php scripts/setup_local_database.php
```

El script valida que el datadir corresponde a este proyecto, crea una base
nueva, aplica 001/002 y carga `003_seed_local.sql`. No sobrescribe un `.env`
existente ni borra/reinicializa una base ya creada. Genera `.env` privado y un
secreto JWT aleatorio; no se suben a Git. Si ocurre un fallo parcial, revisar
la salida antes de reintentar: no hay limpieza destructiva automática.

## Datos precargados

Diez tablas del esquema original, sin columnas ni contratos nuevos:

- 3 banners → `api/banners_listar.php`.
- 2 cupones → `api/promociones_listar.php`.
- 3 publicaciones → `api/publicaciones_listar.php`.
- 4 productos y sus presentaciones → `api/catalogo_listar.php`.
- Cobertura postal precargada por la migración original.

Todos los anuncios y productos están identificados como ejemplos locales.
Los precios/inventario son ficticios y los cupones no son ofertas comerciales.
Su vigencia es de 30 días desde la instalación; después dejan de aparecer
según el filtro real de la API. Para probar otras vigencias, editar las fechas
en la tabla `cupones` usando el usuario local.

El feed requiere una relación con `social_tokens`: se inserta un registro
ficticio, expirado y sin credenciales válidas, únicamente para esa relación.
No existe integración real con Meta, SMTP, pagos o IA en este entorno. Tampoco
se crean usuarios administrativos automáticamente. La clave vacía solicitada
corresponde al usuario de MariaDB, no al login del sitio.

Para ver cambios, editar las tablas locales y recargar la portada. El hero
editorial, los textos institucionales y las fotos de categorías siguen siendo
HTML estático; las secciones de anuncios, promociones y publicaciones sí se
obtienen desde las tablas existentes.

## Verificación

Se comprobaron por HTTP las cinco APIs locales: banners (3), promociones (2),
publicaciones (3), catálogo (4) y código postal 23000 cubierto. No se efectuaron
solicitudes a servicios externos ni se modificó el esquema remoto.
El health-check general incluye SMTP; puede fallar en ese apartado porque no
se ha configurado correo local, aunque las APIs de la portada funcionen.

Verificación adicional en Chromium: portada sin modo demo, 3 banners, 2 cupones,
3 publicaciones y 4 productos renderizados; cobertura confirmada para 23000;
cero solicitudes a mock-data y todas las respuestas API comprobadas en HTTP 200.

## Imágenes externas de portada (prueba, 2026-09-14)

Aplicado `004_imagenes_externas_local.sql` a esta instancia local: tres banners
y cupones LOCAL10/LOCAL50 usan fotografías genéricas de Picsum mediante HTTPS.
No representan productos reales. La migración puede repetirse y solo actualiza
los registros de prueba identificados; no modifica el esquema. Para aplicar
la misma prueba a una instalación nueva, ejecutar este SQL después del seed.
Abrir sin `?demo=1`: la API devuelve `imagen_url` desde MariaDB. Los templates
de banners y promociones ya no incluyen un src local de respaldo; si la imagen
externa falla, se oculta y se conserva el contenido textual del anuncio.
Logotipos e imágenes institucionales siguen siendo assets del sitio.

## Imágenes originales restauradas (2026-09-14)

Por corrección del usuario, se aplicó `005_imagenes_originales_local.sql`:
los tres banners y dos cupones vuelven a las imágenes WebP originales de
`assets/img`. La API sigue leyendo las rutas de MariaDB; los templates no
imponen imágenes. Este ajuste sustituye la prueba externa de 004.
