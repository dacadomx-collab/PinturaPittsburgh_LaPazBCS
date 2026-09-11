# FUENTE DE VERDAD CONSOLIDADA
## PinturaPittsburgh_LaPazBCS — Bitácora de Instanciación

> Este documento nació como el índice maestro de la plantilla genérica (machote)
> "Bóveda Madre" de DCD LABS / VECTOR_CERO. El 2026-09-11 este repositorio dejó
> de ser el machote y se instanció como el proyecto comercial real
> **PinturaPittsburgh — Distribuidor Autorizado en La Paz, B.C.S.** Este archivo
> ahora documenta el checklist de clonación ejecutado y el estado real de cada
> capa, no una plantilla vacía.

---

## 1. MODELO DE 4 CAPAS INMUTABLES

| Capa | Componentes | Estado |
| :--- | :--- | :--- |
| **LAYER_0 — Security** | `helpers/input_sanitizer.php`, `validators/validator.php`, `api/conexion.php` (`ATTR_EMULATE_PREPARES => false`) | ✅ Activo |
| **LAYER_1 — Data** | `api/jwt.php` (HS256, Access/Refresh, Device Binding), `api/auth_middleware.php`, `api/auth_login.php`, `api/auth_refresh.php` | ✅ Activo |
| **LAYER_2 — Observability** | `helpers/asfl_logger.php` (solo `APP_ENV=local`) | ✅ Activo |
| **LAYER_3 — UX** | `assets/css/main.css` (ARF-Grid + paleta "Ocean Breeze & Pittsburgh Gold"), `assets/js/main.js`, `index.html` | ✅ Rebrandeado 2026-09-11 |
| Knowledge Base (`knowledge/00`–`07`) | ✅ Pilares 00, 01, 03, 04, 07 instanciados con datos reales del negocio. Pilares 05/06 marcados N/A donde no aplica el modelo SaaS de la plantilla. |
| Schema de Base de Datos | ✅ **Aprobado y materializado** en [`database/001_schema_inicial.sql`](database/001_schema_inicial.sql) (Directiva 1, 2026-09-11). Pendiente solo de ejecutarse contra un servidor MySQL/MariaDB real al contratar hosting. |
| Scripts de arranque (`scripts/*`) | ✅ Sin cambios — genéricos, no requieren datos del proyecto. |
| Túnel Proxy Seguro para ChatBot IA (`validators/proxy_tunnel_validator.php`, `helpers/ai_runtime_factory.php`) | ⬜ **Inactivo por diseño.** PinturaPittsburgh no revende IA a terceros; el asistente de IA es de uso administrativo interno (ver `knowledge/06_NUCLEO_COGNITIVO_Y_PROMPTS.md`). No activar sin autorización explícita. |

## 2. HALLAZGO DE SEGURIDAD RESUELTO — `core/.env`

Al clonar, existía un directorio `core/.env` con credenciales residuales de un
proyecto ajeno (`tourfindy.com`, hosting GreenGeeks, firmado "Arquitecto: David
(DCD LABS)"). No era leído por ningún código real (`api/conexion.php` lee la
`.env` de la raíz del proyecto, no `core/`). Se **eliminó** el 2026-09-11 por
representar contaminación cruzada entre proyectos del holding, no solo dead
code. Contraseñas en ese archivo estaban vacías — no hubo fuga de secretos
activos, pero sí de nombres de host/usuario de un cliente distinto.

## 3. PENDIENTE DE AUTORIZACIÓN EXPLÍCITA (Mandamiento #9)

- Schema de catálogo/inventario, cobertura postal, pedidos y tokens sociales
  cifrados: **aprobado 2026-09-11 (Directiva 1)** y materializado en
  `database/001_schema_inicial.sql`. Pendiente únicamente ejecutarlo contra un
  servidor real al contratar hosting.
- Dominio de producción — aún no registrado (ver `CLAUDE.md` §1).
- Contratación de hosting/proveedor de producción — aún no definido.

## 4. CHECKLIST DE CLONACIÓN (ejecutado 2026-09-11)

1. ✅ Copiado a `C:\xampp\htdocs\PinturaPittsburgh_LaPazBCS\`.
2. ✅ `CLAUDE.md` §1 completado con identidad real.
3. ⬜ `.env` real — **pendiente**, se crea al contratar hosting/BD.
4. ✅ Repositorio Git inicializado localmente (`git init`, sin remoto aún).
5. ✅ Schema real aprobado y materializado en `database/001_schema_inicial.sql` (Directiva 1, 2026-09-11).
6. ⬜ `api/status_check.php` — pendiente de ejecutar una vez exista `.env` real con BD activa.
7. ⬜ Scanner perimetral AXON DCD — pendiente, se ejecuta antes de producción (Mandamiento #18).

## 5. REFERENCIAS

- Manual operativo del agente: [`CLAUDE.md`](CLAUDE.md)
- Mandamientos y protocolos: [`knowledge/01_LEY_Y_PROTOCOLOS_DE_VUELO.md`](knowledge/01_LEY_Y_PROTOCOLOS_DE_VUELO.md)
- Codex y schema maestro: [`knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md`](knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md)
- Contratos de API: [`knowledge/03_CONTRATOS_API_Y_RUTAS.md`](knowledge/03_CONTRATOS_API_Y_RUTAS.md)
- Fuente de negocio real: [`knowledge/Estrategia Omnicanal y Plataforma Web Pintura Pittsburgh.txt`](knowledge/Estrategia%20Omnicanal%20y%20Plataforma%20Web%20Pintura%20Pittsburgh.txt)
