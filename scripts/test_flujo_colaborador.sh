#!/bin/bash
# =============================================================================
# scripts/test_flujo_colaborador.sh — PinturaPittsburgh_LaPazBCS
# Suite de pruebas del flujo de colaborador externo (Rafael) — Hito 13.
#
# Uso:
#   scripts/test_flujo_colaborador.sh <password> [base_url]
#
# La contraseña de Rafael NUNCA se hardcodea aquí (Mandamiento #12, Bóveda
# de Secretos) — se pasa como argumento en la propia terminal del
# Arquitecto, igual que con scripts/seed_admin.php. Requiere que Rafael ya
# exista en la BD (ver api/setup_diagnostico.php?action=seed_colaborador o
# `php scripts/seed_admin.php armandocastillejos086@gmail.com "<password>" colaborador`).
#
# Pasos:
#   a) POST api/auth_login.php con las credenciales de Rafael — valida
#      HTTP 200 y que el JWT devuelto traiga role=colaborador.
#   b) GET api/banners_listar.php      — valida HTTP 200 + status=success.
#   c) GET api/promociones_listar.php  — valida HTTP 200 + status=success.
#   d) GET api/publicaciones_listar.php — valida HTTP 200 + status=success.
# =============================================================================

set -u

EMAIL="armandocastillejos086@gmail.com"
PASSWORD="${1:-}"
BASE_URL="${2:-http://localhost/PinturaPittsburgh_LaPazBCS}"

if [ -z "$PASSWORD" ]; then
    echo "Uso: scripts/test_flujo_colaborador.sh <password> [base_url]"
    echo "  <password>  Contraseña real de Rafael (nunca se guarda en este script)."
    echo "  [base_url]  Por defecto: http://localhost/PinturaPittsburgh_LaPazBCS"
    exit 2
fi

FALLOS=0

echo "== Paso a) Login de Rafael (rol esperado: colaborador) =="
RESPUESTA_LOGIN=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/api/auth_login.php" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"device_id\":\"test-flujo-colaborador\"}")
HTTP_LOGIN=$(echo "$RESPUESTA_LOGIN" | tail -n1)
BODY_LOGIN=$(echo "$RESPUESTA_LOGIN" | sed '$d')
echo "HTTP $HTTP_LOGIN"
echo "$BODY_LOGIN"

if [ "$HTTP_LOGIN" != "200" ]; then
    echo "FALLO: login devolvió HTTP $HTTP_LOGIN (se esperaba 200)."
    FALLOS=$((FALLOS + 1))
else
    ROL=$(echo "$BODY_LOGIN" | grep -o '"role"[[:space:]]*:[[:space:]]*"[^"]*"' | head -n1 | sed 's/.*:[[:space:]]*"\(.*\)"/\1/')
    if [ "$ROL" = "colaborador" ]; then
        echo "OK: role=colaborador"
    else
        echo "FALLO: se esperaba role=colaborador, se obtuvo '$ROL'."
        FALLOS=$((FALLOS + 1))
    fi
fi

verificar_endpoint_publico() {
    NOMBRE="$1"
    RUTA="$2"
    echo ""
    echo "== $NOMBRE =="
    RESPUESTA=$(curl -s -w "\n%{http_code}" "$BASE_URL$RUTA")
    HTTP_CODE=$(echo "$RESPUESTA" | tail -n1)
    BODY=$(echo "$RESPUESTA" | sed '$d')
    echo "HTTP $HTTP_CODE"
    echo "$BODY"

    if [ "$HTTP_CODE" != "200" ]; then
        echo "FALLO: HTTP $HTTP_CODE (se esperaba 200)."
        FALLOS=$((FALLOS + 1))
        return
    fi

    if echo "$BODY" | grep -q '"status"[[:space:]]*:[[:space:]]*"success"'; then
        echo "OK: HTTP 200 + status=success"
    else
        echo "FALLO: el JSON no reporta status=success."
        FALLOS=$((FALLOS + 1))
    fi
}

verificar_endpoint_publico "Paso b) Banners (Contrato 11)" "/api/banners_listar.php"
verificar_endpoint_publico "Paso c) Promociones (Contrato 12)" "/api/promociones_listar.php"
verificar_endpoint_publico "Paso d) Publicaciones (Contrato 10)" "/api/publicaciones_listar.php"

echo ""
if [ "$FALLOS" -eq 0 ]; then
    echo "RESULTADO: todas las pruebas pasaron."
    exit 0
else
    echo "RESULTADO: $FALLOS prueba(s) fallaron."
    exit 1
fi
