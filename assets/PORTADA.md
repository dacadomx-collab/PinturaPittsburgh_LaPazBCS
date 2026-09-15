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

## Portada compacta (2026-09-14)

La clase `home--landing` limita los ajustes a Inicio. Se redujeron el espaciado
vertical de las secciones, la escala de títulos y el relleno de tarjetas y pie.
El banner coloca imagen y texto lado a lado desde 40rem; en móvil se apilan.
Las imágenes conservan su contenido completo con `object-fit: contain` y una
altura máxima relativa. Promociones mantiene ARF-Grid, con separación de 1rem.
Los controles conservan un alto mínimo de 2.75rem y los efectos existentes.
Todo el estilo reside en `assets/css/main.css`, sin inline ni `!important`.

Verificación con Chromium y datos demo locales en anchos 360, 390, 768, 1024
y 1440: carga de banners, cambio de diapositiva y ausencia de desbordamiento
horizontal. Inspección de captura completa en escritorio y `git diff --check`
correctos. No se modificaron contratos de datos ni se repitió Lighthouse.

## Carruseles automáticos (2026-09-14)

Banners y promociones comparten `initSlider` en `home-data.js`, con avance
cada seis segundos, anterior/siguiente, contador y Pausar/Reproducir.
Se muestra una tarjeta por turno. Se conservan templates, hooks originales,
ARF-Grid y los cuatro estados de datos. Con un solo elemento no hay rotación
ni controles. El puntero pausa temporalmente; el foco pausa hasta Reproducir.
Una pestaña oculta suspende el temporizador y la preferencia de movimiento
reducido desactiva el arranque automático. Las actualizaciones automáticas no
se anuncian por aria-live. Sin cambios de API ni esquema.

Verificación Chromium con datos demo y reloj simulado a 390 y 1440: avance
automático de ambos carruseles, pausa por foco, vuelta al inicio por control
manual y ausencia de desbordamiento horizontal. Sintaxis JS y diff correctos.

El esquema existente `database/001_schema_inicial.sql` define `productos`
con imagen principal en `can_cut_url` (ruta/URL, no binario) y
`producto_presentaciones` para SKU, volumen, precio y stock. No define una
galería independiente de múltiples imágenes por producto.

## Puntos de navegación (2026-09-14)

Por petición del usuario, se sustituyen Anterior/Siguiente y Pausar/Reproducir
por puntos seleccionables, con `aria-current`, nombres accesibles y área táctil
de 2.75rem. Cada tarjeta entra mediante un fundido y desplazamiento de .4rem
durante .5s. Sigue el avance cada seis segundos, suspendido con puntero, foco,
pestaña oculta o movimiento reducido; al abandonar el carrusel se reanuda.
Con movimiento reducido, selección manual y sin animación. Los cuatro estados
y contratos siguen intactos. Verificados selección por puntos, una tarjeta
visible e inexistencia de desbordamiento a 360, 390, 768, 1024 y 1440 con Chromium.
Sintaxis JS y diff correctos.

El esquema `002_banners_cupones_colaborador.sql` también incluye `banners`
y `cupones`, ambos con `imagen_url`. Guardan rutas/URLs de imágenes, además
de textos, destinos y configuración de orden o vigencia respectivamente.

## Ritmo automático y fotos originales (2026-09-14)

Ambos carruseles avanzan cada 3500 ms. Conservan puntos, transición suave y
pausas por interacción, pestaña oculta y movimiento reducido. Las cinco rutas
de anuncios se restauraron en la base local mediante 005; las imágenes son
las originales de assets, seleccionadas por los registros de la API.
Verificados en Chromium avance automático a 3.5 segundos y carga de las cinco
imágenes originales desde las rutas recibidas; sintaxis JS y diff correctos.

## Corrección de rotación y herramientas compactas

Se retiró la pausa por hover de toda la sección: mantenía promociones detenidas
con el puntero encima. Los puntos pulsados con ratón ya no bloquean la rotación;
se conserva pausa por foco de teclado o enlaces del contenido. Ambos carruseles
avanzan a 3500 ms cuando visibles. IntersectionObserver suspende sus timers
fuera de pantalla; la pestaña oculta y movimiento reducido también los suspenden.
La transición usa únicamente opacity/transform, sin filtros animados ni bucles
por frame. No se añadieron dependencias ni capas will-change permanentes.

Calculadora conserva un único título principal, retira el título editorial
repetido y organiza el formulario en dos columnas desde 40rem. Cobertura y
Calculadora usan `home--tool-page`: ancho máximo compartido, márgenes compactos
y campos adaptables. Cobertura elimina blur de la tarjeta y hace visible el
borde del campo postal. Todo CSS sigue en main.css, con los contratos intactos.

Verificación Chromium a 390 y 1440: promociones avanza con el puntero encima,
formularios funcionales (cobertura con respuesta controlada), un h1 por página,
sin overflow ni excepciones JS. Capturas revisadas de móvil y escritorio.
Sintaxis JS y diff correctos; no se hizo una medición Lighthouse nueva.

## Información oficial de Famza y campos de herramientas

Por instrucción explícita del usuario, Inicio incorpora el nombre comercial
«Famza the colour boutique by Pittsburgh Paints», 36 años de experiencia,
matriz en Mariano Abasolo 3114, Plaza Susana, Pueblo Nuevo, 23060, La Paz,
y sucursal en Matamoros/Revolución de 1910, Ildefonso Green, 23470, Cabo San Lucas.
Datos suministrados por el usuario; direcciones ampliadas a partir de los
propios destinos Maps enviados, sin investigación externa. Enlaces Maps
simplificados manteniendo los destinos. Actualizada la dirección antigua en
Tienda y el mensaje de recogida del frontend para evitar contradicciones.

El usuario confirmó WhatsApp 612 157 0249 y horarios L–V 08:00–16:30,
S 08:00–13:00; domingo cerrado. Se publicaron teléfono 612 125 8171,
ambos correos y el perfil Instagram indicado. Facebook aparece por nombre,
sin inventar URL. No se publica TikTok, todavía no abierto, ni el RFC vacío
ni la razón social truncada «Productos Famza…». Estos datos quedan pendientes.
La información nueva sustituye la denominación anterior en Inicio por
instrucción expresa; las otras páginas conservan sus encabezados actuales.

Campo postal: foco interior fino sin sombra. Área: input decimal con patrón,
filtro beforeinput/input para letras y pegado, admite punto o coma decimal;
validación finita y positiva al calcular. Se preservan IDs y fórmula.
Pruebas Chromium a 390/1440: cuatro tarjetas sin overflow, rechazo de letras
y entrada inválida, cálculo con coma decimal y foco postal sin sombra exterior.
Sintaxis JS y diff correctos. No se midió Lighthouse.

## Redes flotantes e información de sucursales

Flotante compartido en las siete páginas públicas, con PNG locales de Icons8
(`assets/icons`, atribución en pie). Instagram y WhatsApp tienen destinos
confirmados. El usuario eligió WhatsApp como cuarto elemento y enviará los
enlaces pendientes: Facebook y TikTok se presentan atenuados, sin enlace
inventado. En móvil el flotante es horizontal y se reserva espacio al pie.
Foco visible, nombres accesibles y movimiento reducido respetados.

Inicio: bloque institucional azul de marca, cifra de trayectoria destacada y
tarjetas claras con acento cobre. Tienda: un título principal, dos tarjetas
con direcciones oficiales y mapa adyacente; se apilan en móvil. Iframes lazy
con título y enlaces alternativos para indicaciones. Se añadió a la CSP de
`.htaccess` únicamente `frame-src 'self' https://www.google.com`, necesario
para los mapas solicitados; las otras directivas permanecen intactas.

Pruebas Chromium a 390/1440 en Inicio y Tienda: sin overflow, cuatro PNG
cargados, un h1 y dos iframes lazy en Tienda. Se comprobó su marcado, no la
precisión cartográfica ni la CSP bajo Apache (el servidor local es PHP).

## Redes individuales sin fondo

Se retiran fondo, borde y sombra del contenedor flotante. Cada acceso mantiene
su área táctil de 2.75rem y foco visible; siluetas coloreadas en cobre de marca
mediante máscaras CSS a partir de los PNG locales, con hover según el tema.
No se animan filtros ni se añaden dependencias; movimiento reducido conserva
su regla. Si el navegador no admite máscaras, se muestran los PNG originales.

Los enlaces Flaticon enviados son categorías, no recursos individuales.
Pendiente recibir PNG o enlaces individuales para reemplazar exactamente los
logos elegidos. Se conservan por ahora los iconos Icons8 y su atribución real;
no se atribuyen archivos existentes a autores distintos. Facebook/TikTok siguen
pendientes de URL de perfil. Validación estática y diff correctos.

## Facebook confirmado

Se activó Facebook en el flotante de las siete páginas y en el bloque de
contacto de Inicio, con el perfil proporcionado por el usuario:
`https://www.facebook.com/profile.php?id=61593025600484`. Apertura en otra
pestaña con nombre accesible y rel noopener noreferrer. TikTok sigue pendiente.

## Círculos sociales y páginas editoriales compactas

Los cuatro accesos sociales tienen círculo azul marino, borde cobre y silueta
clara; los enlaces activos cambian a cobre con hover/foco. El contenedor sigue
transparente. Se corrigieron referencias al nombre actual `tik-tok.png`.

Nosotros e Inspiración usan `home--editorial`, con menor espaciado vertical,
títulos proporcionados e imágenes acotadas. Inspiración agrupa color e imagen
en una tarjeta, y reduce espacios del feed; Nosotros acerca imagen, texto y
detalle. CSS centralizado, tokens existentes y ARF conservados.
Validación Chromium a 390 y 1440 en ambas páginas: sin desbordamiento, cuatro
PNG cargados, círculos y un h1 por página. Capturas generadas y revisión móvil.
`git diff --check` correcto; sin medición Lighthouse nueva.

## Catálogo compacto con búsqueda y redes sin fondo

Iconos reducidos a 1.25rem, con círculos transparentes de borde cobre; se
conserva área táctil de 2.75rem y foco visible. Sin fondo tampoco en hover.
Productos usa `home--products` para reducir espacios, fotos de categorías y
relleno de tarjetas. Eliminado «Ver ficha completa»; `producto.html` conserva
solo redirección/fallback al catálogo, sin mostrar ficha individual.

Buscador destacado inspirado en la entrada «¿Qué buscas hoy?» de la referencia
Home Depot proporcionada. Busca nombre, línea, superficie, brillo y SKU;
normaliza acentos y combina todos los términos con los filtros existentes.
Usa las páginas de 20 productos del contrato actual de catálogo, sin cambiar
PHP ni inventar parámetros de búsqueda. Cache por combinación de filtros
durante la visita y debounce de 200ms, sin red por cada pulsación. La búsqueda
carga las páginas completas del filtro seleccionado; para catálogos grandes
convendrá ampliar el contrato con búsqueda en servidor. Se conservan cuatro
estados y protección contra respuestas fuera de orden.

Chromium a 390/1440 con respuestas controladas: coincidencia sin acento en
producto 21 (segunda página), combinación con filtro que deja cero resultados,
sin enlaces de ficha ni overflow, círculos transparentes. JS y diff correctos.

## Famza como identidad principal y flotante centrado

La instrucción expresa más reciente establece a Famza como cliente y marca
principal, por encima de las pinturas Pittsburgh. Sustituye las pautas previas
de denominación en la presentación pública. Se revisó `logoOriginal.webp` y
se usó su par AVIF/WebP original en cabeceras/pies de las siete páginas; se
actualizaron nombres y metadatos públicos, también la denominación en checkout.
No se modificaron marcas o nombres comerciales de productos ni el backend.
Nosotros destaca los 36 años de Famza en lugar de la antigüedad del fabricante.
Se mantienen las ubicaciones/contactos confirmados y el perfil Facebook por
ID; el nuevo enlace genérico de Facebook no sustituye el perfil correcto.

Redes: posición fixed al 50% de altura de ventana, columna también en móvil,
con carril reservado para evitar cubrir el contenido. Se suprime el anterior
espacio inferior reservado. IntersectionObserver ajusta a blanco el trazo al
coincidir con el pie azul; sin fondo ni listeners de scroll. Fondos grises de
productos/feed y superficies de tarjetas pasan a la base clara del tema.

Chromium: siete páginas a 390 y 1440, logo nuevo, flotante centrado y sin
desbordamiento horizontal; capturas del pie generadas. Sintaxis JS y diff
correctos. Los originales de assets/img siguen bajo la política existente
de archivos ignorados: deben estar disponibles en el servidor al publicar.

## Logo transparente y flotante sin carril móvil

La edición generativa produjo cuadros dibujados, no alfa real, y fue descartada.
`assets/icons/famza-transparent.svg` incorpora el WebP original sin redibujar
la marca y usa feColorMatrix para eliminar el fondo casi blanco; viewBox acota
el margen vacío. El original permanece intacto. Cabecera/pie usan este recurso
local, conservando colores y tipografía. No hay cuadros simulados ni una nueva
imagen generada en el sitio. Máscara fija, no animada.

Se elimina el padding lateral móvil: el flotante ocupa solo sus iconos y queda
sobre la página. Hover únicamente en puntero preciso, ligero movimiento y
escala, sin el doble anillo anterior; outline reservado al teclado. Se respeta
movimiento reducido. Contraste sobre footer se aplica también en móvil.

Chromium 390/1440: sin padding lateral ni overflow; capturas generadas y logo
transparente revisado visualmente en móvil. Diff correcto.

## Nitidez, halo y nuevas campañas Famza

Logo: contorno vectorial en el SVG contenedor sustituye la eliminación por
color que producía halos. Mantiene los píxeles del WebP original y acota su
presentación a 11rem; no es una reconstrucción HD ni un vector de la tipografía.
Para más detalle a gran escala se requiere el original vectorial o una fuente
más grande. Hover de redes: halo localizado con blur fijo de .4rem, animando
solo opacidad; el símbolo se mantiene legible y movimiento reducido se respeta.

Aplicado `database/006_banners_famza_local.sql` únicamente a la instancia propia
verificada: cuatro banners idempotentes con color_collection, salvia, image_3
y SpeedCryl. Textos de prueba y enlaces a asesoría/catálogo, sin precios ni
ofertas inventadas. Ahora hay siete banners activos. Inspiración reutiliza el
mismo endpoint, template y carrusel de Inicio (3.5s, puntos y cuatro estados),
sin nueva columna ni contratos de API. Sin `?demo=1` se muestran los registros
reales; el fixture de demo no incluye estas nuevas campañas.

Chromium 390/1440: siete puntos, cuatro imágenes nuevas cargadas desde BD,
sin overflow, capturas de logo/hover generadas y móvil revisado. Diff correcto.

## Campos discretos y mascota Pitahaya

Los campos `.field` del sitio público (incluidos búsqueda, selectores y
checkout) usan borde cobre en hover y foco fino interior, sin sombra ni
transformación. Se conserva un indicador de teclado visible sin la separación
exagerada heredada del foco general.

Nosotros incorpora una tarjeta adaptable de Pitahaya con los originales
AVIF/WebP aportados, proporción 4:5, carga diferida y texto alternativo. Imagen
completa sin recorte ni ampliación, texto breve y enlace a Inspiración.
Pruebas Chromium a 390/1440: foco de campos visibles en Productos, Calculadora,
Cobertura y Checkout sin sombra/desplazamiento, mascota cargada y Nosotros sin
overflow. Capturas generadas; diff correcto. Backend intacto.

## Tarjetas editoriales como banners en BD

Por autorización expresa, ambas tarjetas de Nosotros y la tarjeta ilustrada
de color pasan a la tabla banners. `007_ubicacion_banners.sql` añade ubicacion
(VARCHAR, default inicio); `008_editoriales_famza_local.sql` carga los textos
actuales, incluyendo «Pita, tu Pitahayita de confianza», sin duplicados.
Pita tiene orden 1 y Famza orden 2 en ubicacion nosotros; color del año usa
inspiracion. Los siete banners anteriores conservan inicio. La API agrega
ubicacion a su respuesta y desempata por id. El frontend filtra por ubicación
y comparte una petición por página; los banners editoriales son tarjetas
estáticas, no un carrusel, y tienen Loading/Empty/Success/Error y template.

La tarjeta de Pita está arriba, sin borde ni fondo propio. Se respeta el nombre
ajustado por el usuario. Promociones/publicaciones ya se alimentan de sus tablas;
se conservan sus contratos. Productos mantiene su excepción, y logotipos,
iconos y mapas son identidad/navegación, no campañas editoriales.

IMPORTANTE para publicar: aplicar 007 a la base destino ANTES de desplegar
la API que selecciona ubicacion; 008 solo corresponde a pruebas locales.
El fixture demo no contiene los nuevos editoriales y muestra Empty; revisar
la base real local sin ?demo=1. No se realizaron escrituras remotas.
Pruebas Chromium 390/1440: Pita primero, dos tarjetas Nosotros, una de color,
siete campañas generales sin contaminación entre ubicaciones, sin overflow.
Empty/Error interceptados verificados. Sintaxis PHP/JS y diff correctos.

## Directiva vigente: sitio informativo, sin ventas en línea

Por instrucción explícita del usuario, NO se realizan ventas desde esta página.
Retirado el enlace al carrito de las siete páginas, formulario de compra y
scripts cart.js, checkout-page.js y producto-page.js (ficha antigua). Eliminado
CSS exclusivo de checkout y ajustados mensajes de calculadora/cobertura.
La URL histórica checkout.html redirige a Tienda y tiene fallback informativo.

La ruta pública api/pedido_crear.php responde 410 sin conexión a BD ni cambios
de stock, para que antiguos clientes tampoco generen pedidos. Esta retirada
sustituye los requisitos previos de compra pública. Se conservan catálogo,
filtros, promociones informativas, contactos y herramientas. No se borraron
tablas, registros históricos ni módulos internos administrativos.

Validado POST local al endpoint: HTTP 410. Sintaxis PHP y diff correctos;
sin referencias a carrito o sus scripts en páginas públicas ni JS activo.

## Corrección persistente de hover y caché CSS (2026-09-15)

La regla genérica de enlaces con :is(.text-link.text-link, ...) tenía mayor
especificidad que la excepción de utility-bar y pintaba el enlace azul sobre
azul. Se excluyó utility-bar de esa regla, manteniendo su hover/foco blanco.
El flotante declara dimensiones max-content y sigue fijo/transparente sin
padding lateral del body. La franja anterior no figura en el CSS vigente.
Como Apache configura caché CSS de un mes, las páginas públicas incluyen ahora
un identificador de contenido en la URL de main.css para invalidar copias viejas.

Chromium 390/1440: hover/foco blanco al terminar la transición, body sin padding
lateral, flotante de 44px y transparente, sin overflow. Diff correcto.

## Relleno estable de redes (2026-09-15)

Por petición del usuario, círculos azul marino con borde cobre y símbolo blanco.
Se retiró el cambio por intersección con footer y su observer: causaba símbolos
blancos sobre superficies claras cuando el pie coincidía parcialmente con el
flotante. Ahora el contraste depende del propio círculo y no de la sección.
Contenedor transparente, posición fija y halo blur conservados. Versionadas
URLs CSS/JS para invalidar caché. Chromium 390/1440 en Cobertura y footer:
colores constantes verificados. Sintaxis JS y diff correctos.

## Redes sin borde (2026-09-15)

Retirado el borde de los círculos por petición del usuario. Se conservan
relleno azul, halo y foco de teclado. URL CSS actualizada para evitar caché.
Validación estática y diff correctos.

## Hover social con halo cobre

Los enlaces sociales activos cambian a cobre y muestran halo desenfocado con
leve desplazamiento/escala en puntero preciso. El símbolo permanece nítido.
Blur fijo de .5rem, animación de opacidad sin bucles JS. Foco de teclado visible
y movimiento reducido respetados. Sin bordes decorativos. Caché CSS renovada;
validación estática y diff correctos.

## Consistencia del flotante entre páginas

Las siete páginas ya compartían versión CSS, pero los PNG no tenían versión
y Apache permitía un año de caché. Se versionaron por hash de contenido los
PNG tanto en HTML como en las máscaras CSS; se recalcularon CSS y home.js.
El HTML se configura con Cache-Control no-cache, must-revalidate para recoger
referencias nuevas; la caché larga de recursos versionados se conserva.
No se asume que la caché fuese la única causa observada en el navegador del
usuario. Chromium local: dos recorridos por las siete rutas, 14 navegaciones,
con estilos idénticos y PNG versionados. La directiva Apache requiere desplegar
.htaccess; no se verificó bajo Apache con el servidor PHP local.
