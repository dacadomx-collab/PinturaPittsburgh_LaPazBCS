<?php

declare(strict_types=1);

// =============================================================================
// admin/social.php — Publicador Social Omnicanal (Hito 12/Directiva 2)
// Migrado desde admin/social.html al shell unificado admin/layout/*.php.
// Contenido interno y IDs sin cambios — mismo contrato con admin-social.js y
// admin-social-page.js (Contrato 6/9).
// =============================================================================

$pageTitle = 'Publicador Social';
$activeNav = 'social';
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/topbar.php';
?>
        <h1>Publicador Social Omnicanal</h1>
        <p class="brand-tagline">Redacta, previsualiza y publica en Facebook e Instagram desde un solo lugar. Instagram solo confirma la publicación final mediante un proceso asíncrono (fase 1 completada aquí).</p>

        <details class="meta-guide">
            <summary class="meta-guide__summary">📋 Guía de Credenciales Requeridas para Meta Graph API</summary>
            <div class="meta-guide__body">
                <p class="brand-tagline">Antes de vincular una cuenta (tabla <code>social_tokens</code>), solicita al administrador de la marca en Meta Business Suite estos 4 accesos. Ninguno se captura en esta pantalla — se entregan de forma segura al equipo técnico para su cifrado y alta directa en base de datos.</p>

                <div class="meta-guide__item">
                    <span class="meta-guide__badge">1</span>
                    <div>
                        <h3 class="meta-guide__titulo">Página de Facebook (Page ID)</h3>
                        <p>Identificador numérico de la Fan Page oficial.</p>
                        <p class="meta-guide__ruta">Configuración de la Página → Información de la página</p>
                    </div>
                </div>

                <div class="meta-guide__item">
                    <span class="meta-guide__badge">2</span>
                    <div>
                        <h3 class="meta-guide__titulo">Instagram Business Account ID</h3>
                        <p>Identificador de la cuenta profesional de Instagram vinculada a la Fan Page.</p>
                        <p class="meta-guide__ruta">Meta Business Suite → Cuentas vinculadas</p>
                    </div>
                </div>

                <div class="meta-guide__item">
                    <span class="meta-guide__badge">3</span>
                    <div>
                        <h3 class="meta-guide__titulo">Meta App ID y App Secret</h3>
                        <p>Credenciales de la aplicación tipo "Business" creada para este proyecto.</p>
                        <p class="meta-guide__ruta">developers.facebook.com → Mis Apps → Configuración básica</p>
                    </div>
                </div>

                <div class="meta-guide__item">
                    <span class="meta-guide__badge">4</span>
                    <div>
                        <h3 class="meta-guide__titulo">System User Token (permanente)</h3>
                        <p>Token de acceso de larga duración, con los permisos: <code>pages_manage_posts</code>, <code>pages_read_engagement</code>, <code>instagram_basic</code>, <code>instagram_content_publish</code>.</p>
                        <p class="meta-guide__ruta">Meta Business Manager → Usuarios del sistema → Generar token</p>
                    </div>
                </div>

                <p class="meta-guide__nota">⚠️ Ninguna de estas 4 credenciales se escribe jamás en código ni en este panel — se cifran con Envelope Encryption (AES-256-GCM) directo en la tabla <code>social_tokens</code> (ver <code>helpers/crypto_helper.php</code>).</p>
            </div>
        </details>

        <div class="arf-grid">
            <section class="card arf-col-2" aria-labelledby="social-form-titulo">
                <h2 id="social-form-titulo">Nueva Publicación</h2>
                <form id="social-form" class="calculator-grid">
                    <div class="field-group">
                        <label for="social-plataforma">Plataforma</label>
                        <select class="field" id="social-plataforma" name="plataforma">
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="ambas">Ambas</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="social-producto">Producto vinculado (opcional)</label>
                        <select class="field" id="social-producto" name="producto_id">
                            <option value="">Sin producto vinculado</option>
                        </select>
                    </div>

                    <div class="field-group field-group--full">
                        <label for="social-texto">Texto / Caption</label>
                        <textarea class="field" id="social-texto" name="texto" required placeholder="Escribe el copy de la publicación. Incluye hashtags locales para Instagram (#LaPazBCS, #Famza)."></textarea>
                    </div>

                    <div class="field-group field-group--full">
                        <label for="social-media-url">URL de la imagen (HTTPS)</label>
                        <input class="field" type="url" id="social-media-url" name="media_url" required placeholder="https://...">
                    </div>

                    <div class="field-group">
                        <label>
                            <input type="checkbox" id="social-publicar-ahora" checked>
                            Publicar ahora
                        </label>
                    </div>

                    <div class="field-group">
                        <label for="social-programado">Programar para (si no es "ahora")</label>
                        <input class="field" type="datetime-local" id="social-programado" name="programado_para" disabled>
                    </div>

                    <div class="field-group">
                        <button type="submit" class="btn btn--gold">Publicar</button>
                    </div>
                </form>
                <p id="social-status" class="postal-bar__result" hidden></p>
            </section>

            <section class="card arf-col-2" aria-labelledby="social-preview-titulo">
                <h2 id="social-preview-titulo">Previsualización en Vivo</h2>
                <div class="preview-panels">
                    <div class="preview-card preview-card--facebook">
                        <p class="preview-card__header">Famza — Facebook</p>
                        <img id="preview-fb-media" class="preview-card__media" alt="Vista previa de imagen para Facebook" hidden>
                        <p id="preview-fb-caption" class="preview-card__caption"></p>
                        <p id="preview-fb-counter" class="char-counter"></p>
                    </div>
                    <div class="preview-card preview-card--instagram">
                        <p class="preview-card__header">Famza — Instagram</p>
                        <img id="preview-ig-media" class="preview-card__media" alt="Vista previa de imagen para Instagram" hidden>
                        <p id="preview-ig-caption" class="preview-card__caption"></p>
                        <p id="preview-ig-counter" class="char-counter"></p>
                    </div>
                </div>
            </section>
        </div>

        <section class="card" aria-labelledby="social-historial-titulo">
            <h2 id="social-historial-titulo">Historial de Publicaciones</h2>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Plataforma</th>
                            <th>Texto</th>
                            <th>Estado</th>
                            <th>Programado para</th>
                            <th>Creado</th>
                        </tr>
                    </thead>
                    <tbody id="social-historial-tbody"></tbody>
                </table>
            </div>
        </section>
<?php
$pageScripts = ['../assets/js/admin-social.js', '../assets/js/admin-social-page.js'];
require __DIR__ . '/layout/footer.php';
?>
