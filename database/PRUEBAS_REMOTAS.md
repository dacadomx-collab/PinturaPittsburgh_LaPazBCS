# Base de pruebas por túnel SSH

La configuración local `.env` utiliza la base aislada de preview entregada por
el equipo. Las credenciales y la llave permanecen en archivos privados ignorados
por Git. Este cambio no configura el `.env` del hosting.

## Iniciar el entorno

Desde la raíz del proyecto, mantener abierta una terminal con:

```bash
bash Llave_SSH_Rafa/tunnel_bd_rafa.sh
```

El túnel escucha en `127.0.0.1:3308`; el puerto 3307 se reserva para la instancia
local anterior. Si indica que 3308 está ocupado, comprobar si el túnel ya está
abierto antes de iniciar otro. Cerrar con Ctrl+C desconecta la base remota.

En otra terminal, si el servidor PHP no está iniciado:

```bash
php -S 127.0.0.1:8000 -t .
```

Abrir http://127.0.0.1:8000/ sin `?demo=1`. No se necesita arrancar MariaDB local.
Los cambios de datos realizados desde la aplicación afectan a la base de pruebas
remota. No ejecutar seeds ni migraciones locales para iniciar este entorno.

## Configuración y recuperación

La llave tiene permisos 600 y el túnel guarda el host conocido en la carpeta
privada. `.env` conserva las opciones de la aplicación y solo cambia la conexión
a la base. La configuración anterior está en
`backups/env_antes_tunel_2026-09-17.env` (privada): para volver a la base local,
restaurarla como `.env` y seguir LOCAL.md.

Verificación: conexión PDO central confirmada y consultas de solo lectura con
10 banners, 2 cupones y 4 productos. No se modificaron datos ni esquema remoto.
