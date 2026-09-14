// Prompt Maestro: presentación Data → Template → Render, sin escrituras al backend.
// Contratos públicos existentes: banners, promociones, publicaciones, productos.
(function (window, document) {
    'use strict';

    var isLocal = ['localhost', '127.0.0.1', '[::1]'].includes(window.location.hostname);
    var demo = isLocal && new URLSearchParams(window.location.search).get('demo') === '1';
    var mockPromise;
    var mockKeys = { banners: 'banners', promociones: 'promotions', publicaciones: 'publications' };
    var demoImages = ['assets/img/personas_pintando_webp.webp', 'assets/img/brocha_webp.webp', 'assets/img/maya_manos_webp.webp'];

    async function fetchJson(url) {
        var controller = new AbortController();
        var timeout = window.setTimeout(function () { controller.abort(); }, 10000);
        try {
            var response = await fetch(url, { signal: controller.signal });
            if (!response.ok) throw new Error('Servicio no disponible');
            return await response.json();
        } finally {
            window.clearTimeout(timeout);
        }
    }

    async function read(endpoint, key) {
        if (demo) {
            // El fixture existente no contiene productos. No se inventa inventario.
            if (key === 'productos') return [];
            if (!mockPromise) mockPromise = fetchJson('mock-data/mock-data.json');
            var mock = await mockPromise;
            var list = mock[mockKeys[key]];
            if (!Array.isArray(list)) throw new Error('Fixture inválido');
            return list.map(function (item, index) {
                var copy = Object.assign({}, item);
                // Sustituir placeholders remotos solo en demo; los campos API no cambian.
                if (key === 'publicaciones') copy.media_url = demoImages[index % demoImages.length];
                else copy.imagen_url = demoImages[index % demoImages.length];
                return copy;
            });
        }
        var body = await fetchJson(endpoint);
        if (body.status !== 'success' || !body.data || !Array.isArray(body.data[key])) {
            throw new Error('Respuesta inválida');
        }
        return body.data[key];
    }

    function setState(section, state) {
        section.querySelectorAll('[data-state]').forEach(function (element) {
            element.hidden = element.dataset.state !== state;
        });
        section.setAttribute('aria-busy', String(state === 'loading'));
    }

    function safeUrl(value) {
        if (typeof value !== 'string' || !value.trim()) return null;
        try {
            var url = new URL(value, document.baseURI);
            if (!['http:', 'https:'].includes(url.protocol)) return null;
            return url.href;
        } catch (_) { return null; }
    }

    function displayDate(element, value) {
        if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}/.test(value)) throw new Error('Fecha inválida');
        var iso = value.slice(0, 10);
        var date = new Date(iso + 'T00:00:00Z');
        if (Number.isNaN(date.getTime()) || date.toISOString().slice(0, 10) !== iso) throw new Error('Fecha inválida');
        element.dateTime = iso;
        element.textContent = date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
    }

    function fillCard(template, item, config) {
        if (!item || typeof item !== 'object' || item.id == null) throw new Error('Elemento inválido');
        config.required.forEach(function (key) {
            if (typeof item[key] !== 'string' || !item[key].trim()) throw new Error('Campo requerido');
        });
        var fragment = template.content.cloneNode(true);
        var card = fragment.firstElementChild;
        card.querySelectorAll('[data-field]').forEach(function (element) {
            var value = item[element.dataset.field];
            if (element.tagName === 'IMG') {
                var fallback = element.getAttribute('src');
                var source = safeUrl(value);
                if (!source && !fallback) throw new Error('Imagen no disponible');
                element.src = source || fallback;
                // La información ya está en el texto adyacente; la imagen es decorativa.
                element.alt = '';
                element.addEventListener('error', function () {
                    if (fallback) element.src = fallback;
                    else element.hidden = true;
                }, { once: true });
            } else if (element.tagName === 'TIME') {
                displayDate(element, value);
            } else {
                element.textContent = value == null ? '' : String(value);
            }
        });
        var link = card.querySelector('[data-cta-link]');
        if (link) {
            var destination = safeUrl(item[config.linkKey]) || 'tienda.html';
            link.href = window.PPPageRoutes ? window.PPPageRoutes.resolve(destination) : destination;
        }
        if (config.key === 'banners') card.dataset.bannerItem = String(item.id);
        if (config.key === 'promociones') card.dataset.promoCode = item.codigo;
        return card;
    }

    function initSlider(section, track, kind) {
        var slides = Array.from(track.children);
        var index = 0;
        var timer;
        var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var focused = false;
        var visible = true;
        var controls = section.querySelector('[data-' + kind + '-controls]');
        var dots = [];
        var live = track.closest('[aria-live]');
        section.setAttribute('aria-roledescription', 'carrusel');
        function schedule() {
            window.clearTimeout(timer);
            var running = !motion.matches && !focused && visible && !document.hidden && slides.length > 1;
            live.setAttribute('aria-live', running ? 'off' : 'polite');
            if (running) timer = window.setTimeout(function () { move(1); }, 3500);
        }
        function show() {
            slides.forEach(function (slide, position) {
                slide.hidden = position !== index;
                dots[position].setAttribute('aria-current', position === index ? 'true' : 'false');
                slide.setAttribute('role', 'group');
                slide.setAttribute('aria-label', (kind === 'banner' ? 'Banner ' : 'Promoción ') + (position + 1) + ' de ' + slides.length);
            });
        }
        function move(step) {
            index = (index + step + slides.length) % slides.length;
            show(); schedule();
        }
        controls.hidden = slides.length < 2;
        slides.forEach(function (slide, position) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'carousel-dot';
            dot.setAttribute('aria-label', 'Ver ' + (kind === 'banner' ? 'banner ' : 'promoción ') + (position + 1) + ' de ' + slides.length);
            dot.addEventListener('click', function () { index = position; show(); schedule(); });
            controls.appendChild(dot);
            dots.push(dot);
        });
        section.addEventListener('focusin', function (event) {
            focused = event.target.matches(':focus-visible') || track.contains(event.target);
            schedule();
        });
        section.addEventListener('focusout', function (event) {
            focused = section.contains(event.relatedTarget); schedule();
        });
        // Suspender trabajo fuera de pantalla, sin listeners de scroll ni sondeo.
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting; schedule();
            });
            observer.observe(track);
        }
        document.addEventListener('visibilitychange', schedule);
        motion.addEventListener('change', schedule);
        show(); schedule();
    }

    async function loadSection(config) {
        var section = document.querySelector(config.selector);
        if (!section) return;
        var target = section.querySelector(config.target || '[data-state="success"]');
        var template = section.querySelector('template');
        setState(section, 'loading');
        try {
            var items = await read(config.endpoint, config.key);
            if (!items.length) { setState(section, 'empty'); return; }
            var fragment = document.createDocumentFragment();
            items.forEach(function (item) { fragment.appendChild(fillCard(template, item, config)); });
            target.replaceChildren(fragment);
            if (config.key === 'banners' || config.key === 'promociones') {
                initSlider(section, target, config.key === 'banners' ? 'banner' : 'promo');
            }
            setState(section, 'success');
        } catch (_) {
            target.replaceChildren();
            setState(section, 'error');
        }
    }

    window.PPHomeData = { read: read, setState: setState, demo: demo };
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-demo-notice]').forEach(function (notice) { notice.hidden = !demo; });
        loadSection({ selector: '[data-banner-slider]', key: 'banners', endpoint: 'api/banners_listar.php', target: '[data-banner-track]', linkKey: 'cta_url', required: ['titulo', 'descripcion', 'cta_texto'] });
        loadSection({ selector: '[data-promo-section]', key: 'promociones', endpoint: 'api/promociones_listar.php', target: '[data-promo-track]', linkKey: 'url', required: ['codigo', 'badge', 'descripcion', 'fecha_inicio', 'fecha_fin'] });
        loadSection({ selector: '[data-social-feed]', key: 'publicaciones', endpoint: 'api/publicaciones_listar.php', required: ['texto', 'publicado_en'] });
    });
})(window, document);
