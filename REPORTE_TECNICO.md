# Reporte Técnico — PinturaPittsburgh_LaPazBCS

**Última actualización:** 2026-09-11 | **Alcance:** arquitectura, seguridad y estado real de la plataforma para cualquier miembro del equipo que se incorpore al proyecto.

> Este archivo vive en la raíz del repositorio (a diferencia de `knowledge/`, que es memoria local del agente IA y **nunca llega a Git**). Es la única fuente de arquitectura que un desarrollador nuevo tiene disponible al clonar el repo.

---

## 1. Qué es esto

E-commerce hiperlocal para **PinturaPittsburgh — Distribuidor Autorizado de The Pittsburgh Paints Company en La Paz, B.C.S.** Combina catálogo/inventario, calculadora de recubrimiento costero, validación de cobertura postal (104 códigos postales de La Paz) y un panel administrativo con publicador social (Meta Graph API) y asistente de contenido con IA — todo sobre un stack PHP nativo sin frameworks.

## 2. Stack Tecnológico

| Capa | Tecnología |
| :--- | :--- |
| Frontend | HTML + CSS + JS nativo (sin build step, sin frameworks) |
| Backend | PHP 8+, `declare(strict_types=1)` en todo archivo |
| Base de Datos | MySQL/MariaDB vía PDO, `ATTR_EMULATE_PREPARES => false` |
| Auth | JWT HS256 (Bearer, sin cookies), Access (15 min) + Refresh (30 días), Device Binding |
| Cifrado de tokens externos | AES-256-GCM Envelope Encryption (KEK + DEK efímera) |
| CI/CD | GitHub Actions → FTP (`SamKirkland/FTP-Deploy-Action`) |
| IA | Claude API / OpenAI, uso exclusivamente interno (nunca expuesto a clientes) |

## 3. Arquitectura de Carpetas

```
api/            Endpoints públicos y protegidos (única puerta HTTP al backend)
api/admin/      Endpoints protegidos por role=admin
admin/          Frontend del backoffice (Bearer JWT vía sessionStorage)
workers/        Scripts CLI/cron — bloqueados por HTTP en .htaccess
database/       Migraciones SQL versionadas — bloqueado por HTTP en .htaccess
helpers/        input_sanitizer, response, asfl_logger, crypto_helper
validators/     Validadores de input (capa opcional de túnel proxy IA, inactiva)
assets/         css/ (main.css + admin.css) y js/ (un módulo por responsabilidad)
scripts/        Herramientas de setup (generate_env, generate_jwt_keys, seed_admin)
```

No existe `CORE/src/` (de una plantilla genérica previa) ni frontend Next.js — la estructura es plana y deliberadamente simple.

## 4. Base de Datos

Schema completo en [`database/001_schema_inicial.sql`](database/001_schema_inicial.sql) — 8 tablas:

| Tabla | Propósito |
| :--- | :--- |
| `users` | Cuentas del backoffice (`admin`/`staff`) |
| `productos` | Catálogo maestro (línea, sustrato, brillo, JSON de propiedades funcionales) |
| `producto_presentaciones` | SKU/precio/stock por volumen (cuarto de galón, galón, cubeta) |
| `codigos_postales_cobertura` | Lista blanca de 104 CP de La Paz (99 sembrados 23000-23098) |
| `pedidos` / `pedido_items` | Checkout — **aún no implementado** en el backend (Contrato 5 pendiente) |
| `social_tokens` | Tokens de Meta Graph API, cifrados (Envelope Encryption) |
| `publicaciones_sociales` | Cola del publicador social (borrador → programada → publicada/fallida) |

**Estado:** schema aprobado y versionado, pero **no ejecutado aún contra ningún servidor real** — bloqueado por credenciales/conectividad pendientes (ver §7).

## 5. Endpoints (contratos completos en `knowledge/03_CONTRATOS_API_Y_RUTAS.md`, no versionado en Git)

| Endpoint | Método | Auth | Estado |
| :--- | :--- | :--- | :--- |
| `api/auth_login.php` | POST | Público | ✅ |
| `api/auth_refresh.php` | POST | Refresh token | ✅ |
| `api/catalogo_listar.php` | GET | Público | ✅ |
| `api/validar_cp.php` | GET | Público | ✅ |
| `api/social_publicar.php` | POST | Bearer + admin | ✅ (Instagram solo fase 1 — ver §8) |
| `api/asistente_ia.php` | POST | Bearer + admin | ✅ |
| `api/admin/catalogo_admin.php` | GET/PUT | Bearer + admin | ✅ |
| `api/admin/social_historial.php` | GET | Bearer + admin | ✅ |
| `api/status_check.php` | GET | Público | ✅ (Triple Handshake) |
| `api/pedido_crear.php` | POST | Público | ⬜ No implementado |

Todo endpoint responde `{ "status": "success"|"error", "message": string, "data": {...} }` y nunca expone `PDOException` ni warnings de PHP crudos al cliente.

## 6. Seguridad

- **Bearer JWT**, nunca cookies. `api/auth_middleware.php` valida firma + expiración + tipo de token (access vs. refresh) antes de dejar pasar a cualquier endpoint protegido.
- **Envelope Encryption AES-256-GCM** (`helpers/crypto_helper.php`) para tokens de Meta Graph API: KEK maestra en `.env` (nunca en BD), DEK efímera por registro, AAD que ata el cifrado a `id`+`plataforma`+`cuenta_id_externa` (un valor cifrado no puede moverse de fila sin invalidar la verificación). Probado con round-trip real y casos negativos (AAD alterado, KEK incorrecta).
- **CORS real**: `api/cors.php` rechaza con `403` cualquier origen fuera de `ALLOWED_ORIGINS`. `Access-Control-Allow-Origin: *` nunca se usa en endpoints que modifican datos.
- **`.htaccess`**: bloquea acceso HTTP directo a `.env`, `.sql`, `.log`, `knowledge/`, `database/`, `scripts/`, `helpers/`, `validators/`, `workers/`.
- **Scripts CLI** (`workers/instagram_worker.php`, `scripts/seed_admin.php`) llevan guardia `PHP_SAPI !== 'cli'` como defensa adicional a la del `.htaccess`.
- **PDO sin emulación de prepares** (`ATTR_EMULATE_PREPARES => false`) — mitigación nativa de inyección SQL en el driver.

## 7. Entornos

| Entorno | Estado |
| :--- | :--- |
| Local (XAMPP) | Funcional — `http://localhost/PinturaPittsburgh_LaPazBCS/` |
| Staging | `https://pittsburgh.tourfindy.com` — hosting/DB asignados, `.env` local generado. **Bloqueante:** contraseña real de BD/SMTP pendiente de recibir; conectividad remota a MySQL (puerto 3306) aún no confirmada desde el equipo de desarrollo (posible whitelist de IP pendiente en cPanel) |
| Producción | Dominio propio de marca aún sin registrar (regla de co-branding: nunca un subdominio del hosting compartido del holding) |

## 8. Automatización y Pendientes Conocidos

- **CI/CD:** `.github/workflows/deploy.yml` — dispara en push a `main`/`master`, excluye `.env`, `knowledge/`, `database/`, `scripts/`, binarios y `.md` (salvo `README.md`). Secrets activos: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`. La ruta remota (`/public_html/pittsburgh/`) está fija en el workflow (no hay `FTP_REMOTE_DIR` como Secret).
- **Worker de Instagram** (`workers/instagram_worker.php`): completa las fases 2/3 del pipeline (sondeo de `status_code` + `media_publish`) que `api/social_publicar.php` no ejecuta de forma síncrona. Requiere cron en el servidor — aún no configurado ahí.
- **`scripts/seed_admin.php`**: aprovisiona el primer usuario admin (bcrypt, sin contraseñas de fábrica adivinables). Requiere que el schema ya esté aplicado en el servidor destino.
- **UI:** toggle Día/Noche (persistido en `localStorage`, contraste WCAG 4.5:1) y botón flotante "volver arriba" implementados en el sitio público y en las 4 pantallas del panel admin.
- **Sin implementar aún:** checkout real (`api/pedido_crear.php`), ficha de producto individual (PDP), carga de los 104 códigos postales oficiales completos (hoy 99 sembrados, el resto marcado "pendiente de verificar SEPOMEX").

## 9. Cómo levantar el entorno local

```bash
# Requiere XAMPP con Apache + PHP 8+
# El proyecto vive en C:\xampp\htdocs\PinturaPittsburgh_LaPazBCS\
http://localhost/PinturaPittsburgh_LaPazBCS/          # sitio público
http://localhost/PinturaPittsburgh_LaPazBCS/admin/    # panel admin (requiere .env + BD + seed_admin.php)
```

No hay paso de build — los archivos HTML/CSS/JS se sirven directo desde el filesystem.
