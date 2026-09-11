<?php

declare(strict_types=1);

// =============================================================================
// helpers/crypto_helper.php — Envelope Encryption (AES-256-GCM) para tokens
// de Meta Graph API almacenados en la tabla `social_tokens`.
// Directiva 3.2 | Especificación: knowledge/04_ARQUITECTURA_Y_BLINDAJE.md §3.2
//
// Jerarquía de claves:
//   KEK (Key Encryption Key) — clave maestra de 256 bits, vive SOLO en
//     .env → META_TOKEN_KEK (base64). Nunca en base de datos, nunca en código.
//   DEK (Data Encryption Key) — clave de 256 bits generada dinámicamente
//     (CSPRNG) en cada cifrado, envuelta con la KEK y guardada junto al dato.
//
// AAD (Additional Authenticated Data): vincula metadatos inmutables del
// registro (id, plataforma, cuenta_id_externa) al cifrado — un valor cifrado
// de una fila no puede moverse a otra fila/columna sin invalidar la
// verificación de integridad (Mandamiento #2: Seguridad Nivel Militar).
//
// Uso:
//   $kek = getenv('META_TOKEN_KEK'); // ver scripts/generate_jwt_keys.php como
//                                     // referencia de patrón para generar KEKs
//   $cifrado = envelopeEncrypt($tokenPlano, $kek, ['id' => $id, 'plataforma' => 'facebook', 'cuenta_id_externa' => $pageId]);
//   // INSERT INTO social_tokens (nonce, dek_envuelta, auth_tag, token_cifrado, ...)
//   //   VALUES ($cifrado['nonce'], $cifrado['dek_envuelta'], $cifrado['auth_tag'], $cifrado['token_cifrado'], ...)
//
//   $tokenPlano = envelopeDecrypt($row, $kek, ['id' => $row['id'], 'plataforma' => $row['plataforma'], 'cuenta_id_externa' => $row['cuenta_id_externa']]);
//   // usar $tokenPlano SOLO dentro de la llamada HTTPS a Meta, luego purgar la variable.
// =============================================================================

const CRYPTO_CIPHER = 'aes-256-gcm';
const CRYPTO_KEY_BYTES = 32;   // 256 bits
const CRYPTO_NONCE_BYTES = 12; // 96 bits — tamaño estándar de IV para GCM
const CRYPTO_TAG_BYTES = 16;   // 128 bits — tamaño de auth_tag de GCM

/**
 * Genera una nueva KEK maestra de 256 bits, codificada en base64 para
 * guardarse en `.env` → META_TOKEN_KEK. Ejecutar una sola vez por entorno;
 * rotarla invalida todos los `social_tokens` existentes (habría que
 * re-cifrarlos con la KEK nueva).
 */
function generateKek(): string
{
    return base64_encode(random_bytes(CRYPTO_KEY_BYTES));
}

/**
 * @param array<string,mixed> $aad Metadatos inmutables a autenticar (nunca secretos).
 * @return array{nonce:string,dek_envuelta:string,auth_tag:string,token_cifrado:string} Binario crudo, listo para bind PDO.
 */
function envelopeEncrypt(string $plaintext, string $kekBase64, array $aad): array
{
    $kek = decodeKek($kekBase64);
    $aadSerialized = serializeAad($aad);

    // 1. DEK efímera (CSPRNG) — nunca se reutiliza entre registros.
    $dek = random_bytes(CRYPTO_KEY_BYTES);

    // 2. Nonce único de 96 bits para el cifrado del dato con la DEK.
    $nonce = random_bytes(CRYPTO_NONCE_BYTES);

    // 3. Cifrar el texto plano con la DEK, autenticando el AAD.
    $authTag = '';
    $tokenCifrado = openssl_encrypt(
        $plaintext,
        CRYPTO_CIPHER,
        $dek,
        OPENSSL_RAW_DATA,
        $nonce,
        $authTag,
        $aadSerialized,
        CRYPTO_TAG_BYTES
    );

    if ($tokenCifrado === false) {
        zeroize($dek);
        throw new \RuntimeException('Fallo al cifrar el token con AES-256-GCM.');
    }

    // 4. Envolver (wrap) la DEK con la KEK maestra — nonce/tag propios del wrap.
    $dekNonce = random_bytes(CRYPTO_NONCE_BYTES);
    $dekAuthTag = '';
    $dekEnvueltaRaw = openssl_encrypt($dek, CRYPTO_CIPHER, $kek, OPENSSL_RAW_DATA, $dekNonce, $dekAuthTag, '', CRYPTO_TAG_BYTES);

    zeroize($dek); // 5. Zeroization — la DEK en texto plano no sobrevive esta función.

    if ($dekEnvueltaRaw === false) {
        throw new \RuntimeException('Fallo al envolver la DEK con la KEK maestra.');
    }

    return [
        'nonce'         => $nonce,
        'dek_envuelta'  => $dekNonce . $dekAuthTag . $dekEnvueltaRaw,
        'auth_tag'      => $authTag,
        'token_cifrado' => $tokenCifrado,
    ];
}

/**
 * @param array{nonce:string,dek_envuelta:string,auth_tag:string,token_cifrado:string} $record Fila de `social_tokens`.
 * @param array<string,mixed> $aad Debe ser IDÉNTICO al usado en envelopeEncrypt() para ese registro.
 */
function envelopeDecrypt(array $record, string $kekBase64, array $aad): string
{
    $kek = decodeKek($kekBase64);

    $dekEnvuelta = (string) $record['dek_envuelta'];
    if (strlen($dekEnvuelta) <= CRYPTO_NONCE_BYTES + CRYPTO_TAG_BYTES) {
        throw new \InvalidArgumentException('dek_envuelta con longitud inválida.');
    }

    $dekNonce       = substr($dekEnvuelta, 0, CRYPTO_NONCE_BYTES);
    $dekAuthTag     = substr($dekEnvuelta, CRYPTO_NONCE_BYTES, CRYPTO_TAG_BYTES);
    $dekEnvueltaRaw = substr($dekEnvuelta, CRYPTO_NONCE_BYTES + CRYPTO_TAG_BYTES);

    $dek = openssl_decrypt($dekEnvueltaRaw, CRYPTO_CIPHER, $kek, OPENSSL_RAW_DATA, $dekNonce, $dekAuthTag, '');
    if ($dek === false) {
        throw new \RuntimeException('No fue posible desenvolver la DEK — KEK incorrecta o dato corrupto.');
    }

    $aadSerialized = serializeAad($aad);

    $plaintext = openssl_decrypt(
        (string) $record['token_cifrado'],
        CRYPTO_CIPHER,
        $dek,
        OPENSSL_RAW_DATA,
        (string) $record['nonce'],
        (string) $record['auth_tag'],
        $aadSerialized
    );

    zeroize($dek);

    if ($plaintext === false) {
        throw new \RuntimeException('Fallo al descifrar el token — auth_tag/AAD no coinciden (posible manipulación o AAD distinto al usado al cifrar).');
    }

    return $plaintext;
}

// ── INTERNOS ─────────────────────────────────────────────────────────────────

function decodeKek(string $kekBase64): string
{
    $kek = base64_decode($kekBase64, true);
    if ($kek === false || strlen($kek) !== CRYPTO_KEY_BYTES) {
        throw new \InvalidArgumentException('META_TOKEN_KEK inválida: se espera una clave de 256 bits en base64 (generar con generateKek()).');
    }

    return $kek;
}

/** @param array<string,mixed> $aad */
function serializeAad(array $aad): string
{
    ksort($aad);

    return json_encode($aad, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

/** Mejor esfuerzo de zeroization en memoria administrada por PHP. */
function zeroize(string &$secret): void
{
    $secret = str_repeat("\0", strlen($secret));
    unset($secret);
}
