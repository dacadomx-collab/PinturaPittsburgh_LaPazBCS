@echo off
REM =============================================================================
REM scripts\tunnel_bd_local.bat — PinturaPittsburgh_LaPazBCS (Hito 18)
REM Abre un tunel SSH que reenvia el puerto local 3307 hacia el MySQL REAL
REM del servidor de staging (localhost:3306 desde la perspectiva del propio
REM servidor) - para que XAMPP local pueda desarrollar y probar contra los
REM mismos datos reales de pittsburgh.tourfindy.com, sin exponer el puerto
REM 3306 del servidor a internet ni depender de que el firewall remoto
REM permita conexiones externas directas.
REM
REM Requisito: la llave SSH ya debe estar autorizada en cPanel, seccion SSH Access
REM (ver knowledge/04_ARQUITECTURA_Y_BLINDAJE.md SS5.5).
REM
REM Uso: doble clic, o "scripts\tunnel_bd_local.bat" desde una terminal.
REM Dejar esta ventana ABIERTA mientras se trabaja en local — cerrarla
REM corta el tunel y el login local vuelve a fallar con "Error de conexion
REM a la base de datos" (mismo sintoma de siempre, no es un error nuevo).
REM
REM NUNCA cambia nada en el servidor ni en el repositorio: .env local usa
REM DB_HOST=127.0.0.1 / DB_PORT=3307 (nunca se sube a Git), y
REM api/conexion.php solo lee DB_PORT si existe -- el .env del servidor no
REM lo define, asi que el deploy normal via GitHub Actions nunca se ve
REM afectado por este tunel.
REM =============================================================================

echo Abriendo tunel SSH: localhost:3307 -^> chir205.websitehostserver.net:3306
echo Deja esta ventana abierta mientras trabajas en local. Ctrl+C para cerrar el tunel.
echo.

ssh -N -o ServerAliveInterval=30 -o ExitOnForwardFailure=yes -i "%USERPROFILE%\.ssh\id_rsa_DavidC_Ajedrez" -p 22 -L 3307:localhost:3306 tourfindycom@chir205.websitehostserver.net
