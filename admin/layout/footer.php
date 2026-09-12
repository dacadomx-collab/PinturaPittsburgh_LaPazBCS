<?php

declare(strict_types=1);

// =============================================================================
// admin/layout/footer.php — Cierre del shell + scripts (Hito 12/Directiva 2)
// Cada página admin/*.php puede definir, ANTES de este require, un arreglo
// $pageScripts con rutas relativas a JS propios de esa vista (ej. lógica de
// admin-catalogo.js), que se cargan siempre DESPUÉS de admin-auth.js.
// =============================================================================

$pageScripts = $pageScripts ?? [];
?>
    </main>
</div>

<button type="button" id="back-to-top-btn" class="back-to-top-btn" hidden aria-label="Volver arriba">↑</button>
</div>

<script src="../assets/js/admin-auth.js" defer></script>
<?php foreach ($pageScripts as $script): ?>
<script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endforeach; ?>
<script src="../assets/js/admin-topbar.js" defer></script>
<script src="../assets/js/theme-toggle.js" defer></script>
<script src="../assets/js/back-to-top.js" defer></script>
</body>
</html>
