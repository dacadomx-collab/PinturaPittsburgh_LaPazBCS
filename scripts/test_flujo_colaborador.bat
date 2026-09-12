@echo off
REM =============================================================================
REM scripts\test_flujo_colaborador.bat — PinturaPittsburgh_LaPazBCS
REM Suite de pruebas del flujo de colaborador externo (Rafael) — Hito 13.
REM Equivalente nativo de Windows a test_flujo_colaborador.sh (usa curl.exe,
REM incluido por defecto desde Windows 10 1803+).
REM
REM Uso:
REM   scripts\test_flujo_colaborador.bat [password] [base_url]
REM
REM La contraseña de Rafael NUNCA se hardcodea aqui (Mandamiento #12, Boveda
REM de Secretos) — se pasa como argumento en la propia terminal del
REM Arquitecto, igual que con scripts\seed_admin.php. Requiere que Rafael ya
REM exista en la BD (ver api/setup_diagnostico.php?action=seed_colaborador o
REM   php scripts\seed_admin.php armandocastillejos086@gmail.com "[password]" colaborador).
REM
REM Pasos:
REM   a) POST api/auth_login.php con las credenciales de Rafael — valida
REM      HTTP 200 y que el JWT devuelto traiga role=colaborador.
REM   b) GET api/banners_listar.php      — valida HTTP 200 + status=success.
REM   c) GET api/promociones_listar.php  — valida HTTP 200 + status=success.
REM   d) GET api/publicaciones_listar.php — valida HTTP 200 + status=success.
REM =============================================================================

setlocal enabledelayedexpansion

set "EMAIL=armandocastillejos086@gmail.com"
set "PASSWORD=%~1"
set "BASE_URL=%~2"
if "%BASE_URL%"=="" set "BASE_URL=http://localhost/PinturaPittsburgh_LaPazBCS"

if "%PASSWORD%"=="" (
    echo Uso: scripts\test_flujo_colaborador.bat ^<password^> [base_url]
    echo   ^<password^>  Contrasena real de Rafael ^(nunca se guarda en este script^).
    echo   [base_url]   Por defecto: http://localhost/PinturaPittsburgh_LaPazBCS
    exit /b 2
)

set /a FALLOS=0
set "TMP_BODY=%TEMP%\pp_test_flujo_body.json"

echo == Paso a^) Login de Rafael ^(rol esperado: colaborador^) ==
curl -s -o "%TMP_BODY%" -w "%%{http_code}" -X POST "%BASE_URL%/api/auth_login.php" ^
    -H "Content-Type: application/json" ^
    -d "{\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\",\"device_id\":\"test-flujo-colaborador\"}" > "%TEMP%\pp_test_flujo_code.txt"
set /p HTTP_LOGIN=<"%TEMP%\pp_test_flujo_code.txt"
type "%TMP_BODY%"
echo.
echo HTTP %HTTP_LOGIN%

if not "%HTTP_LOGIN%"=="200" (
    echo FALLO: login devolvio HTTP %HTTP_LOGIN% ^(se esperaba 200^).
    set /a FALLOS+=1
) else (
    findstr /c:"\"role\":\"colaborador\"" "%TMP_BODY%" >nul
    if !errorlevel! equ 0 (
        echo OK: role=colaborador
    ) else (
        echo FALLO: no se encontro role=colaborador en la respuesta.
        set /a FALLOS+=1
    )
)

call :verificar_endpoint "Paso b) Banners (Contrato 11)" "/api/banners_listar.php"
call :verificar_endpoint "Paso c) Promociones (Contrato 12)" "/api/promociones_listar.php"
call :verificar_endpoint "Paso d) Publicaciones (Contrato 10)" "/api/publicaciones_listar.php"

del /q "%TMP_BODY%" "%TEMP%\pp_test_flujo_code.txt" >nul 2>&1

echo.
if %FALLOS% equ 0 (
    echo RESULTADO: todas las pruebas pasaron.
    exit /b 0
) else (
    echo RESULTADO: %FALLOS% prueba^(s^) fallaron.
    exit /b 1
)

:verificar_endpoint
echo.
echo == %~1 ==
curl -s -o "%TMP_BODY%" -w "%%{http_code}" "%BASE_URL%%~2" > "%TEMP%\pp_test_flujo_code.txt"
set /p HTTP_CODE=<"%TEMP%\pp_test_flujo_code.txt"
type "%TMP_BODY%"
echo.
echo HTTP %HTTP_CODE%

if not "%HTTP_CODE%"=="200" (
    echo FALLO: HTTP %HTTP_CODE% ^(se esperaba 200^).
    set /a FALLOS+=1
    exit /b 0
)

findstr /c:"\"status\":\"success\"" "%TMP_BODY%" >nul
if !errorlevel! equ 0 (
    echo OK: HTTP 200 + status=success
) else (
    echo FALLO: el JSON no reporta status=success.
    set /a FALLOS+=1
)
exit /b 0
