// Compatibilidad con enlaces de campañas y marcadores de la antigua portada.
(function (window, document) {
    'use strict';
    var routes = {
        '#productos': 'productos.html', '#catalogo': 'productos.html#catalogo',
        '#inspiracion': 'inspiracion.html', '#nosotros': 'nosotros.html',
        '#calculadora': 'calculadora.html', '#postal': 'cobertura.html',
        '#contacto': 'tienda.html'
    };
    var base = new URL('.', window.location.href);
    function resolve(value) {
        var url = new URL(value, document.baseURI);
        if (url.origin === base.origin && routes[url.hash] &&
            (url.pathname === base.pathname || url.pathname === base.pathname + 'index.html' || value.startsWith('#'))) {
            var destination = new URL(routes[url.hash], base);
            destination.search = url.search;
            url = destination;
        }
        if (['localhost', '127.0.0.1', '[::1]'].includes(window.location.hostname) &&
            new URLSearchParams(window.location.search).get('demo') === '1' &&
            url.origin === base.origin && /\/(index|productos|inspiracion|nosotros|calculadora|cobertura|tienda)\.html$/.test(url.pathname)) {
            url.searchParams.set('demo', '1');
        }
        // Mantener la misma edición al seguir CTAs devueltos por la API.
        var currentVersion = new URLSearchParams(window.location.search).get('v');
        var homeLink = document.querySelector('.home-brand[href]');
        var version = currentVersion || (homeLink && new URL(homeLink.href).searchParams.get('v'));
        if (version && url.origin === base.origin && url.pathname.startsWith(base.pathname) &&
            /\/(index|productos|inspiracion|nosotros|calculadora|cobertura|tienda)\.html$/.test(url.pathname)) {
            url.searchParams.set('v', version);
        }
        return url.href;
    }
    window.PPPageRoutes = { resolve: resolve };
    function redirectLegacy() {
        if ((window.location.pathname === base.pathname || window.location.pathname === base.pathname + 'index.html') && routes[window.location.hash]) {
            window.location.replace(resolve(window.location.href));
        }
    }
    redirectLegacy();
    window.addEventListener('hashchange', redirectLegacy);
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('a[href]').forEach(function (link) {
            if (!link.getAttribute('href').startsWith('#')) link.href = resolve(link.getAttribute('href'));
        });
    });
})(window, document);
