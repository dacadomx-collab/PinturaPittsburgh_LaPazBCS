// Reubicación de presentación autorizada: mismos formularios y contratos.
// dialog nativo mantiene el foco dentro de la ventana y permite cerrar con Escape.
(function (document, window) {
    'use strict';
    if (typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;
    var tools = new Map();
    ['calculadora', 'postal'].forEach(function (id) {
        var content = document.getElementById(id);
        var dialog = document.getElementById(id + '-dialog');
        if (!content || !dialog) return;
        dialog.querySelector('[data-tool-slot]').appendChild(content);
        tools.set('#' + id, dialog);
        var opener;
        dialog.addEventListener('close', function () {
            document.body.classList.remove('tool-is-open');
            if (tools.get(window.location.hash) === dialog) {
                window.history.replaceState(null, '', window.location.pathname + window.location.search);
            }
            var target = opener && opener.getClientRects().length ? opener : document.querySelector('.menu-toggle');
            if (target && target.getClientRects().length) target.focus();
        });
        dialog.querySelector('[data-tool-close]').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (event) {
            var rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
        });
        dialog.addEventListener('tool-open', function () {
            opener = document.activeElement;
            if (!dialog.open) dialog.showModal();
            document.body.classList.add('tool-is-open');
            dialog.querySelector('input').focus({ preventScroll: true });
        });
    });
    document.querySelectorAll('a[href="#calculadora"], a[href="#postal"]').forEach(function (link) {
        link.setAttribute('aria-haspopup', 'dialog');
        link.setAttribute('aria-controls', link.getAttribute('href').slice(1) + '-dialog');
    });
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var dialog = tools.get(link.getAttribute('href'));
        if (dialog) {
            event.preventDefault();
            dialog.dispatchEvent(new Event('tool-open'));
        } else if (link.closest('.tool-dialog') && link.getAttribute('href').startsWith('#')) {
            link.closest('.tool-dialog').close();
        }
    });
    function openFromHash() {
        var dialog = tools.get(window.location.hash);
        if (dialog) dialog.dispatchEvent(new Event('tool-open'));
    }
    window.addEventListener('hashchange', openFromHash);
    openFromHash();
})(document, window);
