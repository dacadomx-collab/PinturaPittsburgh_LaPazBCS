// Propuesta/propuesta.js — Cotización ACADEP para Famza
// Botón de impresión + firma digital dibujable (canvas, mouse y táctil).
// Sin dependencias externas — mismo criterio del resto del sitio.
document.addEventListener('DOMContentLoaded', function () {

    // ── Botón flotante de impresión ─────────────────────────────────────────
    var printBtn = document.getElementById('proposal-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    // ── Fecha de hoy precargada (el cliente la puede cambiar si firma otro día) ──
    var fechaInput = document.getElementById('firma-fecha');
    if (fechaInput && !fechaInput.value) {
        var hoy = new Date();
        var yyyy = hoy.getFullYear();
        var mm = String(hoy.getMonth() + 1).padStart(2, '0');
        var dd = String(hoy.getDate()).padStart(2, '0');
        fechaInput.value = yyyy + '-' + mm + '-' + dd;
    }

    // ── Firma digital (canvas) ───────────────────────────────────────────────
    var canvas = document.getElementById('firma-canvas');
    if (!canvas || !canvas.getContext) {
        return;
    }
    var ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0f172a';

    var dibujando = false;
    var ultimoPunto = null;

    function posicionRelativa(evento) {
        var rect = canvas.getBoundingClientRect();
        var escalaX = canvas.width / rect.width;
        var escalaY = canvas.height / rect.height;
        var punto = (evento.touches && evento.touches[0]) ? evento.touches[0] : evento;
        return {
            x: (punto.clientX - rect.left) * escalaX,
            y: (punto.clientY - rect.top) * escalaY
        };
    }

    function iniciarTrazo(evento) {
        evento.preventDefault();
        dibujando = true;
        ultimoPunto = posicionRelativa(evento);
    }

    function trazar(evento) {
        if (!dibujando) {
            return;
        }
        evento.preventDefault();
        var punto = posicionRelativa(evento);
        ctx.beginPath();
        ctx.moveTo(ultimoPunto.x, ultimoPunto.y);
        ctx.lineTo(punto.x, punto.y);
        ctx.stroke();
        ultimoPunto = punto;
    }

    function terminarTrazo() {
        dibujando = false;
        ultimoPunto = null;
    }

    // Mouse
    canvas.addEventListener('mousedown', iniciarTrazo);
    canvas.addEventListener('mousemove', trazar);
    canvas.addEventListener('mouseup', terminarTrazo);
    canvas.addEventListener('mouseleave', terminarTrazo);

    // Táctil (dedo o lápiz óptico)
    canvas.addEventListener('touchstart', iniciarTrazo, { passive: false });
    canvas.addEventListener('touchmove', trazar, { passive: false });
    canvas.addEventListener('touchend', terminarTrazo);
    canvas.addEventListener('touchcancel', terminarTrazo);

    var limpiarBtn = document.getElementById('firma-limpiar-btn');
    if (limpiarBtn) {
        limpiarBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    }
});
