# Portada — cumplimiento del Prompt Maestro

Actualizado: 2026-09-12.

Referencia visual: https://www.pittsburghpaintsco.com/. Identidad local:
PinturaPittsburgh — Distribuidor Autorizado en La Paz, B.C.S.

Se aplicaron `Colaboradores/Prompt_Maestro_Portable_Asistenes_IA_Colaboradores.txt`,
el checklist de `Colaboradores/onboarding_colaborador.html` y las reglas de
presentación de `CLAUDE.md`. Se consultó `FUENTEDEVERDAD_CONSOLIDADA.md`, cuya
información de infraestructura es anterior al manual más reciente. La carpeta
`knowledge/` y su ledger no están presentes en esta copia.

## Checklist implementado

| Requisito | Implementación |
| --- | --- |
| ARF-Grid | Catálogo, categorías, líneas, beneficios, filtros, paleta, enlaces y bloques dinámicos usan Flex, wrap y centrado. Dimensiones relativas, límites y aspect-ratio. |
| CSS ORO | Toda la portada está en `assets/css/main.css`; se eliminó `home.css`. Sin estilos inline ni declaraciones `!important`. Colores mediante tokens definidos en `:root`, incluidos los temas. |
| Banners | `section[data-banner-slider]`, `template[data-banner-template]`, `div.banner-slide[data-banner-item]`, imagen WebP de respaldo, título y enlace con apariencia de botón. Carrusel manual sin autoplay, controles accesibles y una sola diapositiva visible. |
| Promociones | `section[data-promo-section]`, `template[data-promo-template]`, `article.promo-card[data-promo-code]`, badge, código y fechas de vigencia visibles con elementos `time`. |
| Publicaciones | `div[data-social-feed]`, `template[data-social-template]`, imagen, plataforma, texto y fecha. |
| Cuatro estados | Loading, Empty, Success y Error explícitos mediante `data-state` y `hidden` en banners, promociones, publicaciones y catálogo; `aria-live` y `aria-busy`. |
| Favicon | `.ico` y el SVG existente de marca enlazados en el head con `sizes="any"`. |
| SEO local | Encabezado “La Paz, B.C.S.”, título y descripción locales, microdatos HardwareStore y PostalAddress con nombre y dirección documentados. `robots.txt` público válido. |

No se inventaron teléfono, horarios, precios reales ni coordenadas. El SVG
original se reutilizó sin alterar su diseño. Las imágenes siguen disponibles
en AVIF/WebP, con carga diferida salvo la imagen principal.

## Archivos y alcance

- `index.html`: marcado semántico, plantillas, estados, favicon y microdatos.
- `assets/css/main.css`: estilos compartidos originales y estilos de portada;
  los tokens de marca de la portada se aplican al body `.home` para conservar
  la apariencia de las otras vistas. Valores de dimensiones convertidos a rem.
- `assets/js/home-boot.js`: prepara la navegación antes del primer paint.
- `assets/js/home.js`: conserva los selectores y controles del menú existente.
- `assets/js/home-data.js`: lectura de datos, clonado de templates, render,
  selección de estados y navegación manual de banners.
- `assets/js/catalog-render.js`: estados explícitos y protección frente a
  respuestas de filtros que llegan fuera de orden; conserva campos y rutas.
- `robots.txt`: recurso estático de SEO; no modifica configuración de servidor.

No se modificaron PHP, SQL, `.env`, `.htaccess`, autenticación ni servicios.
No se añadieron dependencias de compilación a la aplicación. Los menús y las
barras mantienen sus identificadores y controles.

## Datos y demostración local

Vista normal: http://127.0.0.1:8000/

Demostración: http://127.0.0.1:8000/?demo=1

El modo demo solo se activa en localhost, 127.0.0.1 o IPv6 loopback. Lee el
archivo existente `mock-data/mock-data.json`; no escribe datos ni simula
canjes. Muestra avisos visibles que distinguen ejemplos de ofertas reales.
El fixture usa `promotions` y `publications`: el adaptador local los traduce
a las claves de los contratos `promociones` y `publicaciones`. Se sustituyen
sus imágenes placeholder por WebP locales solo durante la demostración.
El fixture no contiene inventario: el catálogo demo muestra Empty.

Fuera de demo, se conservan las rutas públicas:

- `api/banners_listar.php` → `data.banners`.
- `api/promociones_listar.php` → `data.promociones`.
- `api/publicaciones_listar.php` → `data.publicaciones`.
- `api/catalogo_listar.php` → `data.productos`.

Una respuesta HTTP fallida, JSON inválido, campos requeridos ausentes o un
array faltante conduce a Error; un array vacío conduce a Empty. Las consultas
tienen un timeout de diez segundos. Los textos se insertan con `textContent`;
los enlaces e imágenes aceptan únicamente HTTP(S), con respaldo local. Las
fechas se muestran sin desplazar el día por la zona horaria del navegador.

La calculadora mantiene su cálculo original. La consulta de código postal
mantiene su API real incluso en demo: no se simula cobertura territorial.
La vista normal necesita configuración de backend para mostrar datos reales;
sin ella, los bloques presentan Error y el navegador puede registrar los
HTTP fallidos. La demostración de listas no realiza llamadas a las APIs.

## Validación medida

Chromium, ocho anchos: 320, 390, 540, 768, 800, 1024, 1440 y 1920 píxeles.

- Sin desbordamientos horizontales; ARF verificado con estilos computados.
- Tres templates; estados Loading, Empty, Success, Error y JSON incompleto
  probados mediante interceptación de respuestas únicamente en el navegador.
- Carrusel: anterior/siguiente y una sola diapositiva visible.
- Cupones: código asociado y vigencia visible; publicaciones con fecha.
- Menú móvil y Escape; cambio claro/oscuro; calculadora: 32 m² lisos en
  interior → 1 galón de referencia.
- Recursos de imagen cargados; demo sin solicitudes a API al abrir la página.
- Paleta original del checkout conservada tras centralizar el CSS.
- Sin excepciones JavaScript; sintaxis JS y `git diff --check` correctos.

Lighthouse, perfil móvil, sobre `/?demo=1` en el servidor PHP local:

| Categoría | Puntuación |
| --- | ---: |
| Rendimiento | 99 |
| Accesibilidad | 100 |
| Buenas prácticas | 100 |
| SEO | 100 |

CLS: 0. FCP: 1.4 s. LCP: 2.2 s. La meta 100/100 de rendimiento no se alcanzó;
quedan recomendaciones de tamaño de recursos, CSS compartido no utilizado y
caché/compresión del servidor. No se cambió el servidor para mejorar el número.
Estas cifras corresponden a una medición local con fixtures; no certifican
producción, disponibilidad del backend ni posicionamiento en buscadores.

## Distribución

El `.gitignore` existente excluye `assets/img/*`, excepto `logo.svg`.
Las imágenes suministradas están presentes localmente, pero deben distribuirse
expresamente al publicar la portada. No se alteró esa política ni se desplegó.

Para iniciar la vista local desde la raíz:

```bash
php -S 127.0.0.1:8000 -t .
```

El servidor PHP integrado es de desarrollo y no procesa `.htaccess`.

## Herramientas en navbar — ajuste posterior

La documentación disponible exige conservar las funciones y los identificadores,
pero no prescribe su ubicación permanente en el cuerpo de la portada. Se aplicó
la petición de moverlas al navbar sin modificar la validación del checkout.

- Accesos **Calculadora** y **Cobertura** en el navbar.
- `assets/js/home-tools.js` mueve los mismos nodos a ventanas `dialog` nativas,
  sin duplicar formularios ni cambiar los contratos. Los módulos originales
  de cálculo y cobertura siguen operando sobre los mismos identificadores.
- Se conserva el menú hamburguesa; se añaden enlaces, sin reemplazar su lógica.
- Los enlaces existentes del footer y los fragmentos `#calculadora` / `#postal`
  abren las ventanas. Se puede cerrar con el botón, Escape o el fondo exterior.
- Foco inicial en el campo y retorno al acceso que abrió la ventana; en móvil,
  retorno al botón del menú cuando el enlace queda oculto.
- Sin JavaScript o sin soporte para dialog, los formularios permanecen en sus
  secciones originales. Con JavaScript, la portada deja de mostrar esos bloques.

Validación de este ajuste: sintaxis JavaScript, IDs únicos, enlaces internos,
identificadores originales y `git diff --check`. La prueba en Chromium no pudo
ejecutarse porque su permiso fue rechazado; la comprobación visual y funcional
en navegador queda pendiente para este ajuste. Las cifras Lighthouse y pruebas
anteriores de este documento corresponden a la versión previa al cambio de navbar.

## Espaciado y hover de navbar

Ajuste permitido por las reglas de presentación del Prompt Maestro: se conservan
los selectores, el menú hamburguesa, Flex/wrap/center y los tokens de `:root`.
En escritorio, el grupo de enlaces queda más cerca del logo, con margen libre
a su derecha y separación fluida de 1.1 a 1.6 rem entre opciones.

Hover y foco de teclado comparten un fondo translúcido y un subrayado cobre
interior, sin desplazar elementos. Se conserva la preferencia de movimiento
reducido. Solo se modificó CSS y esta documentación. Validación estática y
`git diff --check`; este ajuste no se midió de nuevo en navegador ni Lighthouse.

## Hover de los enlaces del hero

- “Explora nuestros productos”: fondo blanco cálido y texto azul al pasar el
  cursor o recibir foco; borde claro y sombra. Conserva el cobre en reposo.
- “Encuentra inspiración”: texto y subrayado en un cobre claro.
- “De nuestra tienda a tu próximo espacio”: realce tenue del vidrio, borde
  más visible y fondo translúcido en el icono.
- “Descubre más”: cambio de color y subrayado discreto.

Todos los estados incluyen foco de teclado y mantienen los enlaces originales.
No alteran dimensiones ni distribución. Se usan tokens existentes en main.css;
la preferencia de movimiento reducido desactiva las transiciones y la elevación.
Validación estática, contraste del CTA claro y `git diff --check`; sin una nueva
prueba de navegador ni medición Lighthouse para este ajuste.

## Fondo claro del hero

El hero pasa a base blanca, con un velo blanco sobre la fotografía para asegurar
la lectura. Títulos y enlaces usan azul; texto secundario, gris; el acento del
título conserva el cobre. La tarjeta flotante utiliza vidrio blanco y textos
oscuros. El CTA conserva el hover claro con un borde azul que lo distingue del
fondo. Los enlaces secundarios usan subrayados y realces oscuros sutiles.

El hero permanece claro también al cambiar el tema del resto de la página.
Cambios únicamente de presentación en main.css; enlaces y formularios intactos.
Validación estática y diff, sin nueva comprobación visual de navegador.

## Base blanca extendida a toda la portada

Se corrigió el alcance del cambio: fondo blanco en modo claro para navbar,
barra superior, secciones, contacto y footer, además del hero. Texto azul/gris,
bordes suaves y hovers adaptados al fondo claro. También se ajustó la ventana
de cobertura. Las fotografías y los logotipos conservan sus colores originales;
los logos de letras blancas mantienen una pequeña base azul propia para su lectura.
Se conserva la elección de modo oscuro. Cambios solo de CSS, revisión estática
y diff; no se repitieron pruebas de navegador ni mediciones Lighthouse.

## Fondo hueso

Se reutiliza el token existente `--palette-e9dfd2` (#e9dfd2) como fondo general
en modo claro mediante `--home-paper`. `--home-surface` usa el blanco cálido
existente #fbfaf8 para separar hovers y superficies secundarias. El velo del
hero utiliza el mismo tono hueso con transparencia para evitar un bloque blanco.
Se conserva el modo oscuro y los textos oscuros de la presentación clara.
Para mantener contraste suficiente, el texto secundario usa el gris azulado
ya definido en `--color-text-muted` (#475569 en modo claro).
Validación: contraste de texto azul y gris azulado sobre hueso y `git diff --check`.
Sin nueva prueba de navegador.

## Distribución por secciones según la referencia

Se consultaron el HTML y CSS públicos de pittsburghpaintsco.com: `.section`
y `.footer-dark` usan azul Pantone 655 (#002554), `.section-2` es blanca, y
`.gallery-slider` / `.quick-stack-4` usan gris Cool Gray 10 (#63666a).

Se replica esa distribución con la paleta local aprobada: azul #072757 en
cabecera, hero y footer; blanco en bloques informativos y contacto; gris
#646469 en productos y publicaciones. Los bloques dinámicos adicionales de
banners/promociones permanecen claros. Textos y estados hover se adaptaron a
cada fondo; tarjetas de datos mantienen superficies claras para su lectura.
Este cambio sustituye el fondo hueso uniforme. Se preservan formularios,
contratos, modo oscuro y enlaces. Verificación estática y `git diff --check`;
no se ejecutó una nueva comparación visual en navegador.

## Interacciones coherentes en toda la portada

Se unifican las interacciones en main.css por categoría:

- Botones (incluidos formularios, carrusel, filtros y controles): superficie
  clara, texto azul, contorno cobre y elevación leve; pulsación sin elevación.
- Enlaces de texto: mismo subrayado cobre, con texto oscuro sobre fondos claros
  y tono claro sobre fondos azules/grises.
- Tarjetas que realmente son enlaces: contorno cobre, sombra y realce del icono.
  Las tarjetas informativas no aparentan ser enlaces completos.
- Navegación y acordeón: realce de superficie con línea inferior cobre.

Los efectos se aplican tanto a hover como a foco visible. Se respeta movimiento
reducido y se conservan destinos, lógica, datos y backend. Validación estática
y `git diff --check`; no se realizó una nueva prueba visual ni Lighthouse.

## Nitidez y subrayados de enlaces

Los originales locales tienen resoluciones limitadas: hero 800×275, familia de
productos 500×500, color del año 450×450 y varias fotos de categorías 500×500.
Se limitaron sus tamaños de presentación con max-width relativo y se retiraron
los estiramientos verticales y el zoom de las tarjetas. Los banners ahora
combinan imagen acotada y texto en escritorio. El hero integra la foto mediante
una máscara de transparencia en los bordes, sin desenfoque ni regeneración.
No se editaron ni ampliaron artificialmente los originales. En pantallas de
alta densidad se necesitarán fuentes de mayor resolución para obtener más detalle.

Los enlaces de texto tenían simultáneamente border-bottom y text-decoration.
Ahora se utiliza un único pseudoelemento para el subrayado animado, con movimiento
leve de flechas. Foco visible y movimiento reducido están contemplados.

Validación Chromium en 390, 800, 1440 y 1920 píxeles: imágenes revisadas sin superar
las dimensiones intrínsecas y sin desbordamiento horizontal. Cinco tipos de enlace
probados en hover: sin borde inferior ni text-decoration duplicados, una sola
línea mediante ::after. Captura de escritorio inspeccionada. La máscara final de
bordes no cambia las dimensiones comprobadas. git diff --check correcto.


## Portada conectada a MariaDB local

Se habilitó una base local de pruebas por autorización explícita del usuario,
ampliando el alcance previo de solo frontend. Abrir `/` sin `?demo=1` para
cargar banners, promociones, publicaciones y productos desde MariaDB por las
APIs existentes. Arranque, credenciales locales y datos: `../database/LOCAL.md`.
No se modificaron los contratos ni los endpoints para esta conexión.

## Navegación por páginas públicas (2026-09-12)

A petición del usuario se separó la navegación de la portada. Este apartado
sustituye la organización anterior por anclas y los diálogos de herramientas.
El Prompt Maestro exige conservar controles y contratos, pero no obliga a
mantener las secciones en una sola página. Las nuevas páginas públicas están
comprendidas en la asignación explícita del usuario; no se modificó backend,
base de datos ni configuración.

| Página | Contenido |
| --- | --- |
| `index.html` | Banners y promociones dinámicos exclusivamente en el cuerpo principal. |
| `productos.html` | Líneas de pintura, catálogo abierto y filtros existentes. |
| `inspiracion.html` | Inspiración de color y feed de publicaciones. |
| `nosotros.html` | Presentación del distribuidor y compromisos. |
| `calculadora.html` | Calculadora original en página, sin modal. |
| `cobertura.html` | Consulta postal original en página, sin modal. |
| `tienda.html` | Dirección, microdatos HardwareStore y enlace al mapa. |

Todas comparten navegación, enlace Inicio, pie, tema, favicon y estilos de
`assets/css/main.css`. Cada página tiene h1 y metadatos locales propios, y el
navbar identifica su destino con `aria-current="page"`. Los archivos HTML son
estáticos: al cambiar navegación o pie, actualizar las siete páginas.

`assets/js/page-routes.js` conserva los marcadores antiguos de la portada y
resuelve las anclas de las campañas a sus nuevas rutas sin alterar registros
ni contratos API. Conserva parámetros de consulta y el modo local `?demo=1`
entre estas páginas. `home-data.js` usa este adaptador solo en enlaces CTA;
las imágenes mantienen su resolución de URL original. Cada página carga los
módulos necesarios; `home-tools.js` ya no se carga, y no hay diálogos vacíos.

Validación de esta separación: pruebas Chromium a 390 y 1440 de ancho, siete
páginas sin desbordamiento horizontal ni excepciones JavaScript, un h1 y un
destino activo por página, menú móvil y Escape, cálculo, consulta postal con
respuesta controlada y compatibilidad `index.html#catalogo`. Comprobados
estados Empty/Error mediante respuestas interceptadas de los cuatro bloques
dinámicos. Banners, promociones, catálogo y publicaciones cargaron Success
contra la base local. Se conservaron Loading y los templates originales.
Sintaxis JS y `git diff --check` correctos. No se repitió Lighthouse.
