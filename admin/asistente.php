<?php

declare(strict_types=1);

// =============================================================================
// admin/asistente.php — Asistente de Contenido IA (Hito 12/Directiva 2)
// Migrado desde admin/asistente.html al shell unificado admin/layout/*.php.
// Contenido interno y IDs sin cambios — mismo contrato con admin-asistente.js
// y admin-asistente-page.js (Contrato 7). El "arf-grid" que antes vivía en
// <main> ahora envuelve solo las dos tarjetas (main.css exige display:flex
// en el padre inmediato de los .arf-col-*, y <main> ya es admin-content).
// =============================================================================

$pageTitle = 'Asistente IA';
$activeNav = 'asistente';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <div class="arf-grid">
            <section class="card arf-col-2" aria-labelledby="ia-form-titulo">
                <h1 id="ia-form-titulo">Generador de Contenido</h1>
                <p class="brand-tagline">Uso exclusivamente interno. El contenido generado nunca se publica automáticamente — siempre requiere tu revisión.</p>

                <form id="ia-form" class="calculator-grid">
                    <div class="field-group">
                        <label for="ia-tipo">Tipo de contenido</label>
                        <select class="field" id="ia-tipo" name="tipo">
                            <option value="copy_publicitario">Copy publicitario</option>
                            <option value="metadatos_seo">Metadatos SEO</option>
                            <option value="recomendacion_tecnica">Recomendación técnica</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="ia-producto">Producto</label>
                        <select class="field" id="ia-producto" name="producto_id">
                            <option value="">Selecciona un producto...</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="ia-arquetipo">Arquetipo objetivo (C+R Research)</label>
                        <select class="field" id="ia-arquetipo" name="arquetipo_objetivo">
                            <option value="">General</option>
                            <option value="pequeno_contratista">Pequeño Contratista / Remodelador</option>
                            <option value="gran_contratista">Gran Contratista General</option>
                            <option value="administrador_fincas">Administrador de Fincas</option>
                            <option value="arquitecto">Arquitecto / Diseñador</option>
                            <option value="pintor">Pequeño Pintor (oficio)</option>
                        </select>
                    </div>

                    <div class="field-group" id="ia-plataforma-grupo">
                        <label for="ia-plataforma">Plataforma de destino</label>
                        <select class="field" id="ia-plataforma" name="plataforma">
                            <option value="facebook">Facebook (consultivo / rendimiento)</option>
                            <option value="instagram">Instagram (conciso / visual)</option>
                            <option value="web">Ficha web</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <button type="submit" class="btn btn--gold">Generar</button>
                    </div>
                </form>
                <p id="ia-status" class="postal-bar__result" hidden></p>
            </section>

            <section class="card arf-col-2" aria-labelledby="ia-output-titulo">
                <h2 id="ia-output-titulo">Contenido Generado</h2>
                <div id="ia-output" class="ai-output" hidden>
                    <p id="ia-output-texto"></p>
                </div>
                <div id="ia-output-actions" class="ai-output-actions" hidden>
                    <button type="button" id="ia-copiar-btn" class="btn">Copiar</button>
                    <button type="button" id="ia-transferir-btn" class="btn btn--gold">Transferir al Publicador Social</button>
                </div>
            </section>
        </div>
<?php
$pageScripts = ['../assets/js/admin-asistente.js', '../assets/js/admin-asistente-page.js'];
require __DIR__ . '/layout/footer.php';
?>
