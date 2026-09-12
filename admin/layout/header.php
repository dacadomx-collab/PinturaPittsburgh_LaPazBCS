<?php

declare(strict_types=1);

// =============================================================================
// admin/layout/header.php — Panel Administrativo Unificado (Hito 12/Directiva 2)
// HEAD + apertura del shell. Cada página admin/*.php debe definir, ANTES de
// hacer el require, las variables:
//   $pageTitle   (string) — título de pestaña, ej. "Catálogo y Precios"
//   $activeNav   (string) — clave de admin/layout/sidebar.php para resaltar
//                            el enlace activo: inicio|catalogo|pedidos|social|asistente
// Nunca se valida sesión aquí server-side: la autenticación de este proyecto
// es Bearer JWT en sessionStorage (Hito 2), no cookies/sesión PHP — decisión
// documentada en CLAUDE.md §14 y knowledge/04_ARQUITECTURA_Y_BLINDAJE.md. El
// guard anti-parpadeo (assets/js/admin-guard.js) evita el flash del CHROME;
// ningún dato real se imprime en este HTML, todo llega vía authFetch().
// =============================================================================

$pageTitle = $pageTitle ?? 'Panel Administrativo';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — PinturaPittsburgh Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="../favicon.ico">
    <script src="../assets/js/theme-init.js"></script>
    <script src="../assets/js/admin-guard.js"></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-shell" data-admin-shell>
