<?php

declare(strict_types=1);

// =============================================================================
// helpers/password_policy.php — Motor de Política de Contraseñas (Módulo 01 §7)
// Única fuente de verdad de los 3 perfiles canónicos — cualquier endpoint que
// reciba una contraseña nueva (hoy: ninguno además de este; a futuro: cambio
// de contraseña, aceptación de invitación) debe llamar obtenerPoliticaActiva()
// + passwordCumplePolitica() en vez de hardcodear su propio umbral.
// Fuente: modulos/MODULO_01_LOGIN_Y_ACCESO.md §7.1/§7.2 — funciones de
// referencia PHP del blueprint, copiadas literalmente (no reinterpretadas).
// =============================================================================

/**
 * @return array{longitud_minima:int, requiere_mayuscula:bool, requiere_minuscula:bool, requiere_numero:bool, requiere_simbolo:bool}
 */
function politicaSeguridadDefinicion(string $perfil): array
{
    return match ($perfil) {
        'simple' => ['longitud_minima' => 6, 'requiere_mayuscula' => false, 'requiere_minuscula' => false, 'requiere_numero' => false, 'requiere_simbolo' => false],
        'media'  => ['longitud_minima' => 8, 'requiere_mayuscula' => false, 'requiere_minuscula' => true, 'requiere_numero' => true, 'requiere_simbolo' => false],
        'fuerte' => ['longitud_minima' => 14, 'requiere_mayuscula' => true, 'requiere_minuscula' => true, 'requiere_numero' => true, 'requiere_simbolo' => true],
        default  => politicaSeguridadDefinicion('media'),
    };
}

/** @param array{longitud_minima:int, requiere_mayuscula:bool, requiere_minuscula:bool, requiere_numero:bool, requiere_simbolo:bool} $definicion */
function passwordCumplePolitica(string $password, array $definicion): bool
{
    if (mb_strlen($password) < $definicion['longitud_minima']) {
        return false;
    }

    if ($definicion['requiere_mayuscula'] && preg_match('/[A-Z]/', $password) !== 1) {
        return false;
    }

    if ($definicion['requiere_minuscula'] && preg_match('/[a-z]/', $password) !== 1) {
        return false;
    }

    if ($definicion['requiere_numero'] && preg_match('/[0-9]/', $password) !== 1) {
        return false;
    }

    if ($definicion['requiere_simbolo'] && preg_match('/[^a-zA-Z0-9]/', $password) !== 1) {
        return false;
    }

    return true;
}

/** Única fuente de verdad: `configuracion_seguridad` (fila única, id=1). */
function obtenerPoliticaActiva(PDO $pdo): string
{
    $stmt = $pdo->query('SELECT `politica_password` FROM `configuracion_seguridad` WHERE `id` = 1 LIMIT 1');
    $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

    return (string) ($fila['politica_password'] ?? 'media');
}
