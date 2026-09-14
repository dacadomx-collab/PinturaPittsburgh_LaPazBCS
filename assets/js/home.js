// Interacciones de presentación de la portada. No modifica contratos de API.
(function (document, window) {
    'use strict';

    var nav = document.querySelector('.home-nav');
    var toggle = document.querySelector('.menu-toggle');
    var navigation = document.getElementById('home-navigation');
    var catalog = document.getElementById('catalogo');
    if (!nav || !toggle || !navigation) return;

    nav.classList.add('home-nav--enhanced');
    toggle.hidden = false;

    function closeMenu(returnFocus) {
        navigation.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) toggle.focus();
    }

    toggle.addEventListener('click', function () {
        var isOpen = toggle.getAttribute('aria-expanded') === 'true';
        navigation.classList.toggle('is-open', !isOpen);
        toggle.setAttribute('aria-expanded', String(!isOpen));
    });
    navigation.addEventListener('click', function (event) {
        if (event.target.closest('a')) closeMenu(false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') closeMenu(true);
    });
    document.addEventListener('click', function (event) {
        if (!nav.contains(event.target)) closeMenu(false);
    });
    window.matchMedia('(min-width: 800px)').addEventListener('change', function () { closeMenu(false); });

    // Un enlace al catálogo abre el acordeón antes de la navegación nativa.
    function openCatalogFromHash() {
        if (catalog && window.location.hash === '#catalogo') catalog.open = true;
    }
    document.querySelectorAll('a[href="#catalogo"]').forEach(function (link) {
        link.addEventListener('click', function () { if (catalog) catalog.open = true; });
    });
    window.addEventListener('hashchange', openCatalogFromHash);
    openCatalogFromHash();

    // Contraste del flotante centrado al coincidir con el pie oscuro.
    var footer = document.querySelector('.home-footer');
    var social = document.querySelector('.social-float');
    if (footer && social && 'IntersectionObserver' in window) {
        var observer;
        function observeFooter() {
            if (observer) observer.disconnect();
            var margin = Math.max(0, (window.innerHeight - social.offsetHeight) / 2);
            observer = new IntersectionObserver(function (entries) {
                document.body.classList.toggle('social-over-footer', entries[0].isIntersecting);
            }, { rootMargin: '-' + margin + 'px 0px -' + margin + 'px 0px' });
            observer.observe(footer);
        }
        observeFooter();
        window.addEventListener('resize', observeFooter);
    }
})(document, window);
