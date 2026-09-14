<?php

declare(strict_types=1);

// Retirado por instrucción del cliente: Famza es un sitio informativo.
// Conservar la ruta con 410 para bloquear también clientes antiguos.
// No abrir conexión ni crear pedidos o modificar existencias.
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../helpers/response.php';

send_error('La compra en línea no está disponible. Contacta a Famza para recibir atención en tienda.', 410);
